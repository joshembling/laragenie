<?php

namespace JoshEmbling\Laragenie\Helpers;

use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

trait Indexes
{
    public function getDirectoriesAndFiles(string $user_input)
    {
        $directories_and_files = [];
        $extensions = implode(',', config('laragenie.extensions'));
        $incorrect_paths_and_files = [];
        $paths = explode(',', $user_input);

        foreach ($paths as $path) {
            $path = trim($path);

            if (Str::endsWith($path, config('laragenie.extensions'))) {
                $directory = glob($path);
            } else {
                $directory = glob($path."/*.{{$extensions}}", GLOB_BRACE);
            }

            if ($directory) {
                $directories_and_files[$path] = $directory;
            } else {
                if ($path) {
                    $incorrect_paths_and_files[] = "{$path}";
                }
            }
        }

        if ($directories_and_files && $incorrect_paths_and_files) {
            $this->textError('No files found at '.implode(', ', $incorrect_paths_and_files));

            $select = $this->continueAction();

            if ($select === 'n') {
                $this->userAction();
            }
        } elseif (! $directories_and_files && $incorrect_paths_and_files) {
            $this->textError('No files found at '.implode(', ', $incorrect_paths_and_files));
            $this->userAction();
        }

        return $directories_and_files;
    }

    public function getFilesToIndex()
    {
        $index_action = $this->indexAction();

        $user_path = null;

        if ($index_action === 'y') {
            if (config('laragenie.indexes.directories') && is_array(config('laragenie.indexes.directories'))) {
                $user_path = implode(',', config('laragenie.indexes.directories')).',';
            }

            if (config('laragenie.indexes.files') && is_array(config('laragenie.indexes.files'))) {
                $user_path .= implode(',', config('laragenie.indexes.files'));
            }

            if (! config('laragenie.indexes.directories') && ! config('laragenie.indexes.files')) {
                $this->textError('No directories or files were found in your indexes config.');
                $this->userAction();
            }
        } else {
            $user_path = text('Enter your file path(s)');

            if (! $user_path) {
                $this->textError('You must provide at least one directory or file name.');

                $this->userAction();
            }
        }

        return $user_path;
    }

    public function indexFiles(array $chunks, string $file)
    {
        foreach ($chunks as $idx => $chunk) {
            $vector_response = $this->openai->embeddings()->create([
                'model' => config('laragenie.openai.embedding.model'),
                'input' => $chunk,
            ]);

            if ($vector_response) {
                $pinecone_upsert = $this->pinecone->index(env('PINECONE_INDEX'))->vectors()->upsert(vectors: [
                    'id' => str_replace('/', '-', $file).'-'.$idx,
                    //'values' => array_fill(0, 1536, 0.14),
                    'values' => $vector_response->embeddings[0]->toArray()['embedding'],
                    'metadata' => [
                        'filename' => $file,
                        'text' => $chunk,
                    ],
                ]);
            }
        }
    }

    public function removeIndexedFiles(string $paths)
    {
        //$files = glob($paths);
        $directories_and_files = $this->getDirectoriesAndFiles($paths);

        foreach ($directories_and_files as $dir_file) {

            foreach ($dir_file as $file) {
                $formatted_filename = str_replace('/', '-', $file);
                $this->textWarning('Attempting to remove all "'.$file.'" indexes...');

                for ($i = 0; $i < 1000; $i++) {
                    try {
                        $pinecone_res = $this->pinecone->index(env('PINECONE_INDEX'))->vectors()->fetch([
                            "{$formatted_filename}-{$i}",
                        ]);
                    } catch (\Throwable $th) {
                        $this->textError('There has been an error.');
                        break;
                    }

                    if ($i === 0 && empty($pinecone_res->json()['vectors'])) {
                        $this->textWarning('No indexes were found for the file '.$file);
                        break;
                    }

                    if (config('laragenie.indexes.removal.strict') && $i === 0) {
                        $choice = select(
                            'Vectors have been found, are you sure you want to delete them? 🤔',
                            [
                                'y' => 'Yes',
                                'n' => 'No',
                            ],
                        );

                        if ($choice === 'y') {
                            $this->question("Alright, let's bin those 🚽");
                        } else {
                            $this->textOutput('Nothing has been deleted 😅');
                            break;
                        }
                    }

                    try {
                        $response = $this->pinecone->index(env('PINECONE_INDEX'))->vectors()->delete(
                            ids: ["{$formatted_filename}-{$i}"],
                            deleteAll: false
                        );
                    } catch (\Throwable $th) {
                        $this->textError($th);
                    }

                    if (empty($pinecone_res->json()['vectors'])) {
                        $this->textOutput('Vectors have been deleted that were associated with '.$file);
                        $this->newLine();

                        break;
                    }
                }
            }
        }
    }

    public function flushFiles()
    {
        $this->pinecone->index(env('PINECONE_INDEX'))->vectors()->delete(
            deleteAll: true
        );

        $this->textOutput('All files have been removed.');
        $this->userAction();
    }
}

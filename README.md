# Laragenie - AI built to understand your Laravel codebase

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joshembling/laragenie.svg?style=flat-square)](https://packagist.org/packages/joshembling/laragenie)
[![Total Downloads](https://img.shields.io/packagist/dt/joshembling/laragenie.svg?style=flat-square)](https://packagist.org/packages/joshembling/laragenie)

Laragenie is an AI chatbot that runs on the command line. It will be able to read and understand your Laravel codebase following a few simple steps:

1. Set up your env variables [OpenAI and Pinecone](#openai-and-pinecone)
2. Publish and update the Laragenie config
3. Index your files and/or full directories
4. Ask your questions

It's as simple as that! Accelerate your workflow instantly and collaborate seamlessly with the quickest and most knowledgeable 'colleague' you've ever had.

This is a particularly useful CLI bot that can be used to:

-   Onboard developer's to new projects.
-   Assist both junior and senior developers in understanding the codebase, offering a cost-effective alternative to multiple one-on-one sessions with other developers.
-   Provide convenient and readily available support on a daily basis as needed.

## Contents

-   [Requirements](#requirements)
-   [Installation](#installation)
-   [Useage](#usage)
    -   [OpenAI and Pinecone](#openai-and-pinecone)
    -   [Running Laragenie on the command line](#running-laragenie-on-the-command-line)
    -   [Ask a question](#ask-a-question)
    -   [Index files](#index-files)
    -   [Remove indexed files](#remove-indexed-files)
    -   [Stopping Laragenie](#stopping-laragenie)
-   [Changelog](#changelog)
-   [Contributing](#contributing)
-   [Security Vulnerabilities](#security-vulnerabilities)
-   [Credits](#credits)
-   [Licence](#license)

## Requirements

-   Laravel 10 or greater
-   PHP 8 or greater

This package uses [Laravel Prompts](https://laravel.com/docs/10.x/prompts#fallbacks) which supports macOS, Linux, and Windows with WSL. Due to limitations in the Windows version of PHP, it is not currently possible to use Laravel Prompts on Windows outside of WSL.

For this reason, Laravel Prompts supports falling back to an alternative implementation such as the Symfony Console Question Helper.

## Installation

You can install the package via composer:

```bash
composer require joshembling/laragenie
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laragenie-migrations"
php artisan migrate
```

If you don't want to publish migrations, you must toggle the database credentials in your Laragenie config to false. (See config file details below).

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laragenie-config"
```

This is the contents of the published config file:

```php
return [
    'bot' => [
        'name' => 'Laragenie', // The name of your chatbot
         'welcome' => 'Hello, I am Laragenie, how may I assist you today?', // Your welcome message
        'instructions' => 'Write only in markdown format. Only write factual data that can be pulled from indexed chunks.', // The chatbot instructions
    ],

    'chunks' => [
        'size' => 1000, // Maximum caracters to separate chunks
    ],

    'database' => [
        'fetch' => true, // Fetch saved answers from previous questions
        'save' => true, // Save answers to the database
    ],

    'extensions' => [ // The file types you want to index
        'php',
        'blade.php',
        'js',
    ],

    'indexes' => [
        'removal' => [
            'strict' => true, // User prompt on deletion requests of indexes
        ],
    ],

    'openai' => [
        'embedding' => [
            'model' => 'text-embedding-ada-002', // Text embedding model (OpenAI)
            'max_tokens' => 5, // Maximum tokens to use when embedding
        ],
        'chat' => [
            'model' => 'gpt-4-1106-preview', // Your OpenAI GPT model
            'temperature' => 0.1, // Set temperature on the model
        ],
    ],

    'pinecone' => [
        'topK' => 2, // Pinecone indexes to fetch
    ],
];
```

## Usage

### OpenAI and Pinecone

This package uses [OpenAI](https://openai.com/) to process and generate responses and [Pinecone](https://www.pinecone.io/) to index your data.

You will need to create an OpenAI account with credits, generate an API key and add it to your .env file:

```
OPENAI_API_KEY=your-open-ai-key
```

You will also need to create a Pinecone account.

The easiest way to start is with a free account - create an environment with 1536 dimensions and name it, generate an api key and add these details to your .env file:

```
PINECONE_API_KEY=your-pinecone-api-key
PINECONE_ENVIRONMENT=gcp-starter
PINECONE_INDEX=your-index
```

### Running Laragenie on the command line

Once these are setup you will be able to run the following command from your root directory:

```bash
php artisan laragenie
```

You will get 4 options:

1. Ask a question
2. Index files
3. Remove indexed files
4. Something else

Use the arrow keys to toggle through the options and enter to select the command.

#### Ask a question

**Note: you should only run this action once you have some files indexed in your vector database.**

Type in any question relating to your codebase. Answers can be generated in markdown format with code examples. You will also see the cost of each response (in US dollars), which will help keep close track of the expense. Cost of the response is added to your database, if enabled.

You may want to force AI useage (prevent fetching from the database where available) if you are unsatisfied with the initial answer.

To force AI usage, you will need to end all questions with `--ai` e.g. `Tell me about how Users are saved to the database --ai`.

This will ensure the AI model will re-assess your request, and outputs another answer (this could be the same answer depending on the GPT model you are using).

#### Index files

You can index files in the following ways: 

- Inputting a file name with it's namespace e.g. `App/Models/User.php`
- Inputting a full directory, e.g. `App`
    - If you pass in a directory, Laragenie can only index files within this directory, and not its subdirectories. 
    - To index subdirectories you must explicitly pass the path e.g. `App/Models` to index all of your models
- Inputting multiple files or directories in a comma separated list e.g. `App/Models, tests/Feature, App/Http/Controllers/Controller.php`
- Inputting multiple directories with wildcards e.g. `App/Models/*.php`
    - Please note that the wildcards must still match the file extensions in your `laragenie` config file.

#### Remove indexed files

You can remove indexed files using the exact same methods as above. Select the `Remove data associated with a directory or specific file` prompt as an option.

Strict removal, i.e. warning messages before files are removed, can be turned on/off by changing the 'strict' attribute to false in your config.

```php
'indexes' => [
    'removal' => [
        'strict' => true,
    ],
],
```

You may also remove all indexes by selecting `Remove all chunked data`. **Be warned that this will truncate your entire vector database and cannot be reversed.**

#### Stopping Laragenie

You can stop Laragenie using the following methods:

-   `ctrl + c` (Linux/Mac)
-   Selecting `No thanks, goodbye` in the user menu after at least 1 prompt has run.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Josh Embling](https://github.com/joshembling)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

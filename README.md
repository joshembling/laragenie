# Laragenie - AI built to understand your Laravel codebase

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joshembling/laragenie.svg?style=flat-square)](https://packagist.org/packages/joshembling/laragenie)
[![Total Downloads](https://img.shields.io/packagist/dt/joshembling/laragenie.svg?style=flat-square)](https://packagist.org/packages/joshembling/laragenie)

Laragenie is an AI chatbot that runs on the command line. It will be able to read and understand your Laravel codebase after a few simple steps: 

1. Set up your env variables and Laragenie config details
2. Index your files or directories
3. Ask your questions

It's that easy!

## Contents

-   [Installation](#installation)
-   [Useage](#usage)
    -   [OpenAI and Pinecone](#openai-and-pinecone)
    -   [Running Laragenie on the command line](#running-laragenie-on-the-command-line)
    -   [Ask a question](#ask-a-question)
    -   [Index files](#index-files)
    -   [Remove indexed files](#remove-indexed-files)
    -   [Stopping Laragenie](#stopping-laragenie)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [Licence](#license)

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
        'instructions' => 'Write only in markdown format. Only write factual data that can be pulled from indexed chunks.', // The chatbot instructions
    ],

    'database' => [
        'fetch' => true, // Fetch saved answers from previous questions
        'save' => true, // Save answers to the database
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

    'chunks' => [
        'size' => 1000, // Maximum caracters to separate chunks
    ],

    'indexes' => [
        'removal' => [
            'strict' => true, // User prompt on deletion requests of indexes
        ],
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

Once these are setup you will be able to run the following command from your root directory to get started:

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

Type in any question based around your codebase. 

You may want to force AI useage if you are unsatisfied with the answer (if fetched from your database). If you have database credentials in your Laragenie config set to true such as:

```php
'database' => [
    'fetch' => true, // Fetch saved answers from previous questions
    'save' => true, // Save answers to the database
],
```

You will need to end all questions with `--ai` to force AI useage e.g. `Tell me about how Users are saved to the database --ai`.

Once this is saved, the next time this identical question is asked, the command will always attempt to fetch from the database first. You can toggle these paramaters off if you don't want anything to be saved. 

#### Index files

Index files by inputting a file name with it's namespace e.g.

`App/Models/User.php`

You can also index files by indexing a full directory and using a wildcard to select multiple files e.g.

`App/Models/*` or `App/Models/*.php`

#### Remove indexed files

You can remove indexed files using the same method as above when you select `Remove data associated with a directory or specific file` as an option.

Strict removal, i.e. prompts before files are removed can be turned on/off by toggling the boolean 'strict' attribute in your config.

```php
'indexes' => [
    'removal' => [
        'strict' => true,
    ],
],
```

You can also remove all indexes by selecting `Remove all chunked data`. Be warned that this will truncate your entire vector database.

#### Stopping Laragenie

You can stop Laragenie by either doing `ctrl + c` (Linux/Mac), `ctrl + shift + c` (Windows), or selecting `No thanks, goodbye` in the user menu after 1 prompt has run.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Josh Embling](https://github.com/joshembling)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

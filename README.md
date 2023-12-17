# Laragenie - AI built to understand your Laravel codebase

[![Latest Version on Packagist](https://img.shields.io/packagist/v/joshembling/laragenie.svg?style=flat-square)](https://packagist.org/packages/joshembling/laragenie)
[![Total Downloads](https://img.shields.io/packagist/dt/joshembling/laragenie.svg?style=flat-square)](https://packagist.org/packages/joshembling/laragenie)

Laragenie is an AI chatbot that runs on the command line. It will be able to read and understand your Laravel codebase after a few simple steps: 

1. Set up your env variables [OpenAI and Pinecone](#openai-and-pinecone)
2. Publish and update the Laragenie config
3. Index your files and/or full directories
4. Ask your questions

It's that easy! You will instantly speed up your workflow and start working hand-in-hand with the most knowledgeable colleague you've ever had. 

This is a particularly useful bot that can be used to:
- Onboard developer's to new projects
- Help junior's and senior's get to grips with how the codebase works (a much cheaper scenario than 1-1's with other dev's)
- Have handy on a daily basis whenever and however it is needed.

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

Note: you should only run this action once you have some files indexed.

Type in any question relating to your codebase. Answers can be generated in markdown format with code examples. You will also see the cost of each response (in US dollars), which will help keep close track of the expense. Cost of the response is added to your database, if enabled.

You may want to force AI useage (prevent fetching from the database where available) if you are unsatisfied with the initial answer.

To force AI usage, you will need to end all questions with `--ai` e.g. `Tell me about how Users are saved to the database --ai`.

This will ensure the AI model will re-assess your request, and outputs another answer (this could be the same answer depending on the GPT model you are using). 

#### Index files

You can index files by inputting a file name with it's namespace e.g.

`App/Models/User.php`

You may also index files by inputting a full directory, then use a wildcard (*) to select multiple files e.g.

`App/Models/*` or `App/Models/*.php`

Please note: if you are using the '*' wildcard without an extension, this directory must only contain files and not folders, otherwise an exception will be thrown.

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

You may also remove all indexes by selecting `Remove all chunked data`. Be warned that this will truncate your entire vector database and cannot be reversed.

#### Stopping Laragenie

You can stop Laragenie using the following methods: 
- `ctrl + c` (Linux/Mac)
- `ctrl + shift + c` (Windows)
- Selecting `No thanks, goodbye` in the user menu after at least 1 prompt has run.

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

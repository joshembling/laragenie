<?php

namespace JoshEmbling\Laragenie\Helpers;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

trait Formatting
{
    public function textError(?string $text)
    {
        return error(wordwrap($text, 70, "\n"));
    }

    public function textNote(?string $text)
    {
        return note(wordwrap($text, 70, "\n"));
    }

    public function textOutput(?string $text)
    {
        return info(wordwrap($text, 70, "\n"));
    }

    public function textWarning(?string $text)
    {
        return warning(wordwrap($text, 70, "\n"));
    }
}

<?php

namespace Saia\Pqr\webserviceGenerator;

interface IWsHtml
{
    public function getHtmlContentForm(array $filesToInclude): string;
    public function getJsContentForm(): string;

    public function getHtmlContentSearchForm(array $filesToInclude): string;
    public function getJsContentSearchForm(): string;
}

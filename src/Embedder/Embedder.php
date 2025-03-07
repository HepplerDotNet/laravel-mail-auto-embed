<?php

namespace HepplerDotNet\LaravelMailAutoEmbed\Embedder;

use HepplerDotNet\LaravelMailAutoEmbed\Contracts\Embedder\EntityEmbedder;
use HepplerDotNet\LaravelMailAutoEmbed\Contracts\Embedder\UrlEmbedder;

abstract class Embedder implements UrlEmbedder, EntityEmbedder
{
}

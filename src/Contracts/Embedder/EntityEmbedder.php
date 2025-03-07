<?php

namespace HepplerDotNet\LaravelMailAutoEmbed\Contracts\Embedder;

use HepplerDotNet\LaravelMailAutoEmbed\Models\EmbeddableEntity;

interface EntityEmbedder
{
    /**
     * @return string
     */
    public function fromEntity(EmbeddableEntity $entity);
}

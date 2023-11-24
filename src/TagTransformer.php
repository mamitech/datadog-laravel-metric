<?php

namespace Mamitech\DatadogLaravelMetric;

interface TagTransformer
{
    // transform tags and return the new modified tags
    public function transform(array $tags): array;
}

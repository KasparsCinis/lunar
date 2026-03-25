<?php

namespace Lunar\Enums;

enum DevelopmentUpdateKind: string
{
    case FeatureRequest = 'feature_request';
    case Bug = 'bug';
    case Improvement = 'improvement';
    case Chore = 'chore';

    public function label(): string
    {
        return match ($this) {
            self::FeatureRequest => 'Feature request',
            self::Bug => 'Bug',
            self::Improvement => 'Improvement',
            self::Chore => 'Chore',
        };
    }
}

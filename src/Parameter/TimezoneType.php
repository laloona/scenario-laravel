<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Parameter;

use Stateforge\Scenario\Core\Attribute\AsParameterType;

#[AsParameterType('Validates timezone identifiers such as Europe/Berlin or America/New_York.')]
final class TimezoneType extends StringTypeDefinition
{
    protected function rules(): array
    {
        return [
            'timezone:all',
        ];
    }
}

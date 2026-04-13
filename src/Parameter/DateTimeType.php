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

#[AsParameterType('Validates date and time values in the format YYYY-MM-DD HH:MM:SS.')]
final class DateTimeType extends StringTypeDefinition
{
    protected function rules(): array
    {
        return [
            'date_format:Y-m-d H:i:s',
        ];
    }
}

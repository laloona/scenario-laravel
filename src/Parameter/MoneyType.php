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

#[AsParameterType('Validates monetary amounts greater than or equal to zero with up to two decimal places.')]
final class MoneyType extends FloatTypeDefinition
{
    protected function rules(): array
    {
        return [
            'numeric',
            'gte:0',
            'regex:/^\d+(\.\d{1,2})?$/',
        ];
    }
}

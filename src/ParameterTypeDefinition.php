<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel;

use Illuminate\Support\Facades\Validator;
use Stateforge\Scenario\Core\ParameterTypeDefinition as CoreParameterTypeDefinition;

abstract class ParameterTypeDefinition extends CoreParameterTypeDefinition
{
    final public function cast(mixed $value): string|int|float|bool|null
    {
        $validator = Validator::make(
            ['value' => $value],
            ['value' => $this->rules()],
        );

        return ($validator->fails())
            ? null
            : $this->valueType($value)->value;
    }

    /**
     * @return list<string>
     */
    abstract protected function rules(): array;
}

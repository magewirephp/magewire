<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Component;

use Exception;
use Magewirephp\Magewire\Exception\AcceptableException;
use Rakit\Validation\Validator;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ValidationException;

abstract class Form extends Component
{
    public const COMPONENT_TYPE = 'form';

    /** @var Validator $validator */
    protected $validator;

    /**
     * Validation rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Validation messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * @param Validator $validator
     */
    public function __construct(
        Validator $validator
    ) {
        $this->validator = $validator;
    }

    /**
     * @param array $rules
     * @param array $messages
     * @param array|null $data
     * @param bool $mergeWithClassProperties
     * @return bool
     * @throws AcceptableException
     */
    public function validate(
        array $rules = [],
        array $messages = [],
        array $data = null,
        bool $mergeWithClassProperties = true
    ): bool {
        $rules = $mergeWithClassProperties ? array_merge($this->rules, $rules) : $rules;
        $data = $data ?? $this->getPublicProperties(true);

        $messages = array_map(static function ($message) {
            return __($message);
        }, $mergeWithClassProperties ? array_merge($this->messages, $messages) : $messages);

        try {
            $validation = $this->validator->validate($data, $rules, $messages);

            if ($validation->fails()) {
                foreach ($validation->errors()->toArray() as $key => $error) {
                    $this->error($key, current($error));
                }

                throw new ValidationException(__('Something went wrong while validating the form input'));
            }
        } catch (Exception $exception) {
            throw new AcceptableException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @throws AcceptableException
     */
    public function validateOnly(array $rules = [], array $messages = [], array $data = null): bool
    {
        return $this->validate($rules, $messages, $data, false);
    }
}

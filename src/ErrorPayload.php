<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/11/23 0023
 * Time: 22:41
 * @from https://github.com/phalcon/phalcon-devtools/blob/master/scripts/Phalcon/Error/AppError.php
 */

namespace MyLib\Errors;

/**
 * Class ErrorPayload
 * @package MyLib\Errors
 *
 * @method int type()
 * @method string message()
 * @method string file()
 * @method string line()
 * @method \Exception|null exception()
 * @method bool isException()
 * @method bool isError()
 *
 */
final class ErrorPayload
{
    /**
     * @var array
     */
    private $attributes;

    /**
     *  constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->attributes = array_merge([
            'type' => -1,
            'message' => 'No error message',
            'file' => '',
            'line' => '',
            'exception' => null,
            'isException' => false,
            'isError' => false,
        ], $options);
    }

    /**
     * Magic method to retrieve the attributes.
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed|null
     */
    public function __call($method, $args)
    {
        return $this->attributes[$method] ?? null;
    }
}

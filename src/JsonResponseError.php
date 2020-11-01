<?php
/**
 * @author Alexey Yashin <me@alexey-yashin.ru>
 * Date: 01.11.2020
 * Time: 0:12
 */

namespace AlexeyYashin\Fastresponse;

/**
 * Class JsonError
 * @package AlexeyYashin\Fastresponce
 *
 * @method error(string $error, bool $critical = true): JsonError
 * @method reply(int $code = 200): void
 */
class JsonResponseError
{
    protected $title = '';
    protected $critical = false;
    protected $code = '';
    protected $status = 200;

    protected $jsonReply = null;

    public function __construct(JsonResponse $jsonReply)
    {
        $this->jsonReply = $jsonReply;
    }

    public function title(string $title = null)
    {
        if ($title === null) {
            return $this->title;
        }

        $this->title = $title;

        return $this;
    }

    public function critical(bool $critical = true)
    {
        if ($critical) {
            $this->jsonReply->setCritical();

            if ($this->jsonReply->config(JsonResponse::CONFIG_PUSH_AFTER_CRITICAL)) {
                $this->jsonReply->reply($this->statusCode());
            }
        }

        return $this;
    }

    public function code($code = null)
    {
        if ($code === null) {
            return $this->code;
        }

        $this->code = $code;

        return $this;
    }

    public function statusCode(int $code = null)
    {
        if ($code === null) {
            return $this->status;
        }

        $this->status = $code;

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->jsonReply, $name)) {
            return call_user_func_array([$this->jsonReply, $name], $arguments);
        }
    }
}

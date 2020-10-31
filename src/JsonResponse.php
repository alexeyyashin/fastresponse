<?php
/**
 * @author Alexey Yashin <me@alexey-yashin.ru>
 * Date: 01.11.2020
 * Time: 0:08
 */
 
namespace AlexeyYashin\Fastresponse;

/**
 * Ajax-queries JSON replying tool
 *
 * Class JsonReply
 * @package AlexeyYashin\Fastresponse
 *
 * @author Alexey Yashin
 * @version 1.3
 */
class JsonResponse
{
    /**
     * Response behavior options:
     *
     * Parameter                    | Description                                       | Default value         |
     * -----------------------------|---------------------------------------------------|-----------------------|
     * CONFIG_PUSH_AFTER_CRITICAL   | If `true` immediately after critical error `err`  | true                  |
     *                              | status will be flushed                            |                       |
     * -----------------------------|---------------------------------------------------|-----------------------|
     * CONFIG_GET_HTML_OUTPUT       | If `true` reply will contain `htmlOutput` key     | true                  |
     *                              | where buffer content will be placed. Buffer       |                       |
     *                              | itself will be cleared                            |                       |
     * -----------------------------|---------------------------------------------------|-----------------------|
     * CONFIG_CLEAR_DATA_IF_ERROR   | If `true` after critical error `data` key will    | true                  |
     *                              | be cleaned. Set to `true` by default for          |                       |
     *                              | security reason                                   |                       |
     * -----------------------------|---------------------------------------------------|-----------------------|
     */
    const CONFIG_PUSH_AFTER_CRITICAL = 'pushAfterCritical';
    const CONFIG_GET_HTML_OUTPUT = 'getHtmlOutput';
    const CONFIG_CLEAR_DATA_IF_ERROR = 'clearDataIfError';

    private $errorStack = [];
    private $data = [];
    private $status = 'ok';

    private $hadCriticalError = false;

    private $config = [
        'pushAfterCritical' => true,
        'clearDataIfError' => true,
        'getHtmlOutput' => true,
    ];

    /**
     * Options getter-setter
     *
     * @param string|array $a   string - options key, must be set to static::CONFIG_*
     *                          array -
     *                              options key => value
     * @param mixed|null $b     Has the meaning only if $a is a string
     *                          If `null` - returns current $a option value
     *                          Else - $b is set as new value for $a
     *
     * @return $this|mixed
     */
    public function config($a, $b = null)
    {
        if ($b === null)
        {
            if (is_array($a))
            {
                $this->config = array_merge(
                    $this->config,
                    $a
                );
            }
            else
            {
                return $this->config[$a];
            }
        }
        else
        {
            if (is_string($a))
            {
                $this->config[$a] = $b;
            }
        }

        return $this;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function setCritical()
    {
        $this->hadCriticalError = true;
        return $this;
    }

    public function error($error = null)
    {
        if ($error instanceof JsonResponseError) {
            $errObject = $error;
        } else {
            $errObject = new JsonResponseError($this);
            $errObject->title($error);
        }

        $this->errorStack[] = $errObject;

        return $errObject;
    }

    /**
     * Adds data to response (`data` key)
     *
     * @param string|mixed $a   If $b is set, $a is considered to be a key of assocciated array
     *                          Else $a will be added as an element of numeric array
     * @param mixed|null $b
     *
     * @return $this
     */
    public function addData($a, $b = null)
    {
        if ($b !== null)
        {
            $this->data[$a] = $b;
        }
        else
        {
            $this->data[] = $a;
        }

        return $this;
    }

    /**
     * Sets new data array in response (`data` key)
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets new status
     *
     * @param bool $status
     *
     * @return $this
     */
    public function setStatus(bool $status = true)
    {
        $this->status = $status;

        return $this;
    }

    public function reply(int $code = 200)
    {
        if ($this->hadCriticalError)
        {
            $this->status = false;

            if ($this->config(static::CONFIG_CLEAR_DATA_IF_ERROR)) {
                $this->data = [];
            }
        } else {
            $this->status = true;
        }

        $errors = [];

        foreach ($this->errorStack as $error) {
            $errors[] = [
                'code' => $error->code(),
                'title' => $error->title(),
            ];
        }

        $reply = [
            'status' => $this->status,
            'code' => 200,
            'data' => $this->data,
            'errors' => $errors,
            'html' => $this->config(static::CONFIG_GET_HTML_OUTPUT) ? ob_get_clean() : null,
        ];

        while (@ob_end_clean()) {}

        header('Content-type: application/json; charset=UTF-8', true);
        http_response_code($code);
        echo json_encode($reply);
        exit(0);
    }
}

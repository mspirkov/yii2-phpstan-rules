<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDirectSuperglobalsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDirectSuperglobalsRule>
 */
final class NoDirectSuperglobalsRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoDirectSuperglobals/code.php'],
            [
                ['Direct use of superglobal $_GET is forbidden. Use yii\web\Request::get() or yii\web\Request::getQueryParam() instead.', 3],
                ['Direct use of superglobal $_POST is forbidden. Use yii\web\Request::post() or yii\web\Request::getBodyParam() instead.', 4],
                ['Direct use of superglobal $_FILES is forbidden. Use yii\web\UploadedFile::getInstance() or yii\web\UploadedFile::getInstances() instead.', 5],
                ['Direct use of superglobal $_COOKIE is forbidden. Use yii\web\Request::cookies for reading cookies or yii\web\Response::cookies for writing them instead.', 6],
                ['Direct use of superglobal $_SERVER is forbidden. Use yii\web\Request methods such as getHeaders(), getUserAgent(), or getHostInfo() instead.', 7],
                ['Direct use of superglobal $_REQUEST is forbidden. Use yii\web\Request and read query or body parameters explicitly instead.', 8],
                ['Direct use of superglobal $_SESSION is forbidden. Use yii\web\Session instead.', 9],
            ],
        );
    }

    protected function getRule(): Rule
    {
        return new NoDirectSuperglobalsRule();
    }
}

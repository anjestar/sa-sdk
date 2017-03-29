<?php
namespace Sensor;

define('SENSORS_ANALYTICS_SDK_VERSION', '1.5.0');

class SensorsAnalyticsException extends \Exception
{
}

// 在发送的数据格式有误时，SDK会抛出此异常，用户应当捕获并处理。
class SensorsAnalyticsIllegalDataException extends SensorsAnalyticsException
{
}

// 在因为网络或者不可预知的问题导致数据无法发送时，SDK会抛出此异常，用户应当捕获并处理。
class SensorsAnalyticsNetworkException extends SensorsAnalyticsException
{
}

// 当且仅当DEBUG模式中，任何网络错误、数据异常等都会抛出此异常，用户可不捕获，用于测试SDK接入正确性
class SensorsAnalyticsDebugException extends \Exception
{
}

// 不支持 Windows，因为 Windows 版本的 PHP 都不支持 long
if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN") {
    throw new SensorsAnalyticsException("Sensors Analytics PHP SDK dons't not support Windows");
}







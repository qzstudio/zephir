<?php

/**
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir;

use Zephir\Exception\InvalidArgumentException;

/**
 * Zephir\Utils
 *
 * Utility functions.
 *
 * @package Zephir
 */
class Utils
{
    /**
     * Prepares a class name to be used as a C-string
     *
     * @param string $className
     * @return string
     */
    public static function escapeClassName($className)
    {
        return str_replace('\\', '\\\\', $className);
    }

    /**
     * Prepares a string to be used as a C-string
     *
     * @param string $str
     * @param bool $escapeSlash
     * @return string
     */
    public static function addSlashes($str, $escapeSlash = false)
    {
        $newstr = "";
        $after = null;
        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $ch = substr($str, $i, 1);
            if ($i != ($length -1)) {
                $after = substr($str, $i + 1, 1);
            } else {
                $after = null;
            }

            switch ($ch) {
                case '"':
                    $newstr .= "\\" . '"';
                    break;
                case "\n":
                    $newstr .= "\\" . 'n';
                    break;
                case "\t":
                    $newstr .= "\\" . 't';
                    break;
                case "\r":
                    $newstr .= "\\" . 'r';
                    break;
                case "\v":
                    $newstr .= "\\" . 'v';
                    break;
                case '\\':
                    switch ($after) {
                        case "n":
                        case "v":
                        case "t":
                        case "r":
                        case '"':
                        case "\\":
                            $newstr .= $ch . $after;
                            $i++;
                            break;
                        default:
                            $newstr .= "\\\\";
                            break;
                    }
                    break;
                default:
                    $newstr .= $ch;
            }
        }
        return $newstr;
    }

    /**
     * Camelize a string
     *
     * @param       string $str
     * @return      string
     */
    public static function camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    /**
     * Checks if the content of the file on the disk is the same as the content.
     *
     * @param $content
     * @param $path
     * @return bool
     */
    public static function checkAndWriteIfNeeded($content, $path)
    {
        if (file_exists($path)) {
            $contentMd5 = md5($content);
            $existingMd5 = md5_file($path);

            if ($contentMd5 != $existingMd5) {
                file_put_contents($path, $content);
                return true;
            }
        } else {
            file_put_contents($path, $content);
            return true;
        }

        return false;
    }

    /**
     * Transform class/interface name to FQN format
     *
     * @param string $className
     * @param string $currentNamespace
     * @param AliasManager $aliasManager
     * @return string
     */
    public static function getFullName($className, $currentNamespace, AliasManager $aliasManager = null)
    {
        if (!is_string($className)) {
            throw new InvalidArgumentException('Class name must be a string ' . print_r($className, true));
        }

        // Absolute class/interface name
        if ($className[0] === '\\') {
            return substr($className, 1);
        }

        // If class/interface name not begin with \ maybe a alias or a sub-namespace
        $firstSepPos = strpos($className, '\\');
        if (false !== $firstSepPos) {
            $baseName = substr($className, 0, $firstSepPos);
            if ($aliasManager && $aliasManager->isAlias($baseName)) {
                return $aliasManager->getAlias($baseName) . '\\' . substr($className, $firstSepPos + 1);
            }
        } elseif ($aliasManager && $aliasManager->isAlias($className)) {
            return $aliasManager->getAlias($className);
        }

        // Relative class/interface name
        if ($currentNamespace) {
            return $currentNamespace . '\\' . $className;
        }

        return $className;
    }

    /**
     * Resolves Windows release folder.
     *
     * @return string
     */
    public static function resolveWindowsReleaseFolder()
    {
        if (self::isThreadSafe()) {
            if (PHP_INT_SIZE === 4) {
                // 32-bit version of PHP
                return "ext\\Release_TS";
            } elseif (PHP_INT_SIZE === 8) {
                // 64-bit version of PHP
                return "ext\\x64\\Release_TS";
            } else {
                // fallback
                return "ext\\Release_TS";
            }
        } else {
            if (PHP_INT_SIZE === 4) {
                // 32-bit version of PHP
                return "ext\\Release";
            } elseif (PHP_INT_SIZE === 8) {
                // 64-bit version of PHP
                return "ext\\x64\\Release";
            } else {
                // fallback
                return "ext\\Release";
            }
        }
    }

    /**
     * Checks if current PHP is thread safe.
     *
     * @return boolean true
     */
    public static function isThreadSafe()
    {
        if (defined('PHP_ZTS') && PHP_ZTS == 1) {
            return true;
        }

        ob_start();
        phpinfo(INFO_GENERAL);
        return (bool) preg_match('/Thread\s*Safety\s*enabled/i', strip_tags(ob_get_clean()));
    }
}

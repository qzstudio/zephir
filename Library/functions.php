<?php

/*
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Zephir;

use const INFO_GENERAL;
use const PHP_INT_SIZE;
use const PHP_OS;
use const PHP_ZTS;
use const SCANDIR_SORT_ASCENDING;
use Zephir\Exception\InvalidArgumentException;

/**
 * Attempts to remove recursively the directory with all subdirectories and files.
 *
 * A E_WARNING level error will be generated on failure.
 *
 * @param string $path
 */
function unlink_recursive($path)
{
    if (is_dir($path)) {
        $objects = array_diff(scandir($path, SCANDIR_SORT_ASCENDING), ['.', '..']);

        foreach ($objects as $object) {
            if (is_dir("{$path}/{$object}")) {
                unlink_recursive("{$path}/{$object}");
            } else {
                unlink("{$path}/{$object}");
            }
        }

        rmdir($path);
    }
}

/**
 * Camelize a string.
 *
 * @param string $string
 *
 * @return string
 */
function camelize($string)
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
}

/**
 * Prepares a class name to be used as a C-string.
 *
 * @param string $className
 *
 * @return string
 */
function escape_class($className)
{
    return str_replace('\\', '\\\\', $className);
}

/**
 * Prepares a string to be used as a C-string.
 *
 * @param string $string
 *
 * @return string
 */
function add_slashes($string)
{
    $newstr = '';
    $after = null;
    $length = \strlen($string);

    for ($i = 0; $i < $length; ++$i) {
        $ch = $string[$i];
        if ($i !== ($length - 1)) {
            $after = $string[$i + 1];
        } else {
            $after = null;
        }

        switch ($ch) {
            case '"':
                $newstr .= '\\'.'"';
                break;
            case "\n":
                $newstr .= '\\'.'n';
                break;
            case "\t":
                $newstr .= '\\'.'t';
                break;
            case "\r":
                $newstr .= '\\'.'r';
                break;
            case "\v":
                $newstr .= '\\'.'v';
                break;
            case '\\':
                switch ($after) {
                    case 'n':
                    case 'v':
                    case 't':
                    case 'r':
                    case '"':
                    case '\\':
                        $newstr .= $ch.$after;
                        ++$i;
                        break;
                    default:
                        $newstr .= '\\\\';
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
 * Transform class/interface name to FQN format.
 *
 * @param string       $className
 * @param string       $currentNamespace
 * @param AliasManager $aliasManager
 *
 * @return string
 */
function fqcn($className, $currentNamespace, AliasManager $aliasManager = null)
{
    if (!\is_string($className)) {
        throw new InvalidArgumentException('Class name must be a string, got '.\gettype($className));
    }

    // Absolute class/interface name
    if ('\\' === $className[0]) {
        return substr($className, 1);
    }

    // If class/interface name not begin with \ maybe a alias or a sub-namespace
    $firstSepPos = strpos($className, '\\');
    if (false !== $firstSepPos) {
        $baseName = substr($className, 0, $firstSepPos);
        if ($aliasManager && $aliasManager->isAlias($baseName)) {
            return $aliasManager->getAlias($baseName).'\\'.substr($className, $firstSepPos + 1);
        }
    } elseif ($aliasManager && $aliasManager->isAlias($className)) {
        return $aliasManager->getAlias($className);
    }

    // Relative class/interface name
    if ($currentNamespace) {
        return $currentNamespace.'\\'.$className;
    }

    return $className;
}

/**
 * Checks if the content of the file on the disk is the same as the content.
 *
 * @param string $content
 * @param string $path
 *
 * @return int|bool
 */
function file_put_contents_ex($content, $path)
{
    if (file_exists($path)) {
        $contentMd5 = md5($content);
        $existingMd5 = md5_file($path);

        if ($contentMd5 !== $existingMd5) {
            return file_put_contents($path, $content);
        }
    } else {
        return file_put_contents($path, $content);
    }

    return false;
}

/**
 * Checks if currently running under MS Windows.
 *
 * @return bool
 */
function is_windows()
{
    return 0 === stripos(PHP_OS, 'WIN');
}

/**
 * Checks if currently running under macOs.
 *
 * @return bool
 */
function is_macos()
{
    return 0 === stripos(PHP_OS, 'DARWIN');
}

/**
 * Checks if currently running under BSD based OS.
 *
 * @see   https://en.wikipedia.org/wiki/List_of_BSD_operating_systems
 *
 * @return bool
 */
function is_bsd()
{
    return false !== stripos(PHP_OS, 'BSD');
}

/**
 * Checks if current PHP is thread safe.
 *
 * @return bool
 */
function is_zts()
{
    if (\defined('PHP_ZTS') && PHP_ZTS === 1) {
        return true;
    }

    ob_start();
    phpinfo(INFO_GENERAL);

    return (bool) preg_match('/Thread\s*Safety\s*enabled/i', strip_tags(ob_get_clean()));
}

/**
 * Resolves Windows release folder.
 *
 * @return string
 */
function windows_release_dir()
{
    if (is_zts()) {
        if (PHP_INT_SIZE === 4) {
            // 32-bit version of PHP
            return 'ext\\Release_TS';
        }

        if (PHP_INT_SIZE === 8) {
            // 64-bit version of PHP
            return 'ext\\x64\\Release_TS';
        }

        // fallback
        return 'ext\\Release_TS';
    }

    if (PHP_INT_SIZE === 4) {
        // 32-bit version of PHP
        return 'ext\\Release';
    }

    if (PHP_INT_SIZE === 8) {
        // 64-bit version of PHP
        return 'ext\\x64\\Release';
    }

    // fallback
    return 'ext\\Release';
}

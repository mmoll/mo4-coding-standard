<?php

/**
 * This file is part of the mo4-coding-standard (phpcs standard)
 *
 * @author  Xaver Loppenstedt <xaver@loppenstedt.de>
 *
 * @license http://spdx.org/licenses/MIT MIT License
 *
 * @link    https://github.com/mayflower/mo4-coding-standard
 */

declare(strict_types=1);

namespace MO4\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Variable in Double Quoted String sniff.
 *
 * Variables in double quoted strings must be surrounded by { }
 *
 * @author    Xaver Loppenstedt <xaver@loppenstedt.de>
 *
 * @copyright 2013 Xaver Loppenstedt, some rights reserved.
 *
 * @license   http://spdx.org/licenses/MIT MIT License
 *
 * @link      https://github.com/mayflower/mo4-coding-standard
 *
 * @psalm-api
 */
class VariableInDoubleQuotedStringSniff implements Sniff
{
    /**
     * Regular expression matching variable references inside a double quoted string.
     */
    private const VARIABLE_REGEXP = '/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\->[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*|\[[^\]]*\])?/';

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array<int, string>
     *
     * @see Tokens.php
     */
    public function register(): array
    {
        return [T_DOUBLE_QUOTED_STRING];
    }

    /**
     * Called when one of the token types that this sniff is listening for
     * is found.
     *
     * @param File $phpcsFile The PHP_CodeSniffer file where the
     *                        token was found.
     * @param int  $stackPtr  The position in the PHP_CodeSniffer
     *                        file's token stack where the token
     *                        was found.
     *
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens  = $phpcsFile->getTokens();
        $content = $tokens[$stackPtr]['content'];

        \preg_match_all(self::VARIABLE_REGEXP, $content, $matches, PREG_OFFSET_CAPTURE);

        $toWrap = [];

        foreach ($matches as $match) {
            foreach ($match as [$var, $pos]) {
                if (1 !== $pos && '{' === $content[($pos - 1)]) {
                    continue;
                }

                $before = \substr($content, 0, $pos);

                if (\strpos($before, '{') > 0
                    && !\str_contains($before, '}')
                ) {
                    continue;
                }

                $lastOpeningBrace = \strrpos($before, '{');

                if (false !== $lastOpeningBrace
                    && '$' === $content[($lastOpeningBrace + 1)]
                ) {
                    $lastClosingBrace = \strrpos($before, '}');

                    if (false !== $lastClosingBrace
                        && $lastClosingBrace < $lastOpeningBrace
                    ) {
                        continue;
                    }
                }

                $fix = $phpcsFile->addFixableError(
                    \sprintf(
                        'must surround variable %s with { }',
                        $var
                    ),
                    $stackPtr,
                    'NotSurroundedWithBraces'
                );

                if (true !== $fix) {
                    continue;
                }

                $toWrap[] = [$pos, $var];
            }
        }

        if ([] === $toWrap) {
            return;
        }

        $correctVariable = $content;

        foreach (\array_reverse($toWrap) as [$pos, $var]) {
            $correctVariable = $this->surroundVariableWithBraces(
                $correctVariable,
                $pos,
                $var
            );
        }

        $this->fixPhpCsFile($stackPtr, $correctVariable, $phpcsFile);
    }

    /**
     * Surrounds a variable with curly brackets
     *
     * @param string $content content
     * @param int    $pos     position
     * @param string $var     variable
     *
     */
    private function surroundVariableWithBraces(string $content, int $pos, string $var): string
    {
        $before = \substr($content, 0, $pos);
        $after  = \substr($content, ($pos + \strlen($var)));

        return $before.'{'.$var.'}'.$after;
    }

    /**
     * Fixes the file
     *
     * @param int    $stackPtr        stack pointer
     * @param string $correctVariable correct variable
     * @param File   $phpCsFile       PHP_CodeSniffer File object
     *
     */
    private function fixPhpCsFile(int $stackPtr, string $correctVariable, File $phpCsFile): void
    {
        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($stackPtr, $correctVariable);
        $phpCsFile->fixer->endChangeset();
    }
}

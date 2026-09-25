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

namespace MO4\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Array Double Arrow Alignment sniff.
 *
 * '=>' must be aligned in arrays, and the key and the '=>' must be in the same line
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
class ArrayDoubleArrowAlignmentSniff implements Sniff
{
    /**
     * Define all types of arrays.
     *
     * @var array
     */
    protected array $arrayTokens = [
        T_OPEN_SHORT_ARRAY,
        T_ARRAY,
    ];

    /**
     * Fast membership lookup for array tokens.
     *
     * @var array
     */
    protected array $arrayTokenLookup = [
        T_OPEN_SHORT_ARRAY => true,
        T_ARRAY            => true,
    ];

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return array<int, int>
     *
     * @see    Tokens.php
     */
    public function register(): array
    {
        return $this->arrayTokens;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in
     *                        the stack passed in $tokens.
     *
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens  = $phpcsFile->getTokens();
        $current = $tokens[$stackPtr];

        if (T_ARRAY === $current['code']) {
            $start = $current['parenthesis_opener'];
            $end   = $current['parenthesis_closer'];
        } else {
            $start = $current['bracket_opener'];
            $end   = $current['bracket_closer'];
        }

        if ($tokens[$start]['line'] === $tokens[$end]['line']) {
            return;
        }

        $assignments      = [];
        $keyEndColumn     = -1;
        $lastLine         = -1;
        $assignmentsCount = 0;
        $previousComma    = false;

        for ($i = ($start + 1); $i < $end; $i++) {
            $current = $tokens[$i];

            // Skip nested arrays.
            if (isset($this->arrayTokenLookup[$current['code']])) {
                $i = T_ARRAY === $current['code'] ? ($current['parenthesis_closer'] + 1) : ($current['bracket_closer'] + 1);

                continue;
            }

            // Skip closures in array.
            if (T_CLOSURE === $current['code']) {
                $i = ($current['scope_closer'] + 1);

                continue;
            }

            if (T_COMMA === $current['code']) {
                $previousComma = $i;

                continue;
            }

            if (T_DOUBLE_ARROW !== $current['code']) {
                continue;
            }

            $previous                       = $tokens[($i - 1)];
            $assignments[$assignmentsCount] = $i;
            $assignmentsCount++;

            // Get column info once
            $column = $previous['column'];
            $line   = $current['line'];

            if ($lastLine === $line) {
                $msg = 'only one "=>" assignments per line is allowed in a multi line array';

                if (false !== $previousComma) {
                    $fixable = $phpcsFile->addFixableError($msg, $i, 'OneAssignmentPerLine');

                    if (true === $fixable) {
                        $phpcsFile->fixer->beginChangeset();
                        $phpcsFile->fixer->addNewline((int) $previousComma);
                        $phpcsFile->fixer->endChangeset();
                    }
                } else {
                    // Remove current and previous '=>' from array for further processing.
                    unset($assignments[$assignmentsCount - 1], $assignments[$assignmentsCount - 2]);
                    $assignmentsCount -= 2;
                    $phpcsFile->addError($msg, $i, 'OneAssignmentPerLine');
                }
            }

            // Check if key is on the same line - optimized lookup
            $hasKeyInLine = false;
            $j            = ($i - 1);

            // Stop at the beginning of the line for efficiency
            $lineStart = $current['line'];

            while (($j >= 0) && ($tokens[$j]['line'] === $lineStart)) {
                // Tokens::EMPTY_TOKENS lookup for tokens that do not contribute to a key.
                if (!isset(Tokens::EMPTY_TOKENS[$tokens[$j]['code']])) {
                    $hasKeyInLine = true;

                    break;
                }

                $j--;
            }

            if (false === $hasKeyInLine) {
                $fixable = $phpcsFile->addFixableError(
                    'in arrays, keys and "=>" must be on the same line',
                    $i,
                    'KeyAndValueNotOnSameLine'
                );

                if (true === $fixable) {
                    $phpcsFile->fixer->beginChangeset();
                    $phpcsFile->fixer->replaceToken($j, '');
                    $phpcsFile->fixer->endChangeset();
                }
            }

            // Track max column position
            if ($column > $keyEndColumn) {
                $keyEndColumn = $column;
            }

            $lastLine = $line;
        }

        // Calculate alignment offset
        $doubleArrowStartColumn = ($keyEndColumn + 1);

        // Process assignments with minimal overhead
        foreach ($assignments as $ptr) {
            $current = $tokens[$ptr];
            $column  = $current['column'];

            if ($column === $doubleArrowStartColumn) {
                continue;
            }

            $beforeArrowPtr = ($ptr - 1);
            $currentIndent  = \strlen($tokens[$beforeArrowPtr]['content']);
            $correctIndent  = ($currentIndent - $column + $doubleArrowStartColumn);

            $fixable = $phpcsFile->addFixableError("each \"=>\" assignments must be aligned; current indentation before \"=>\" are {$currentIndent} space(s), must be {$correctIndent} space(s)", $ptr, 'AssignmentsNotAligned');

            if (false === $fixable) {
                continue;
            }

            $phpcsFile->fixer->beginChangeset();

            if (T_WHITESPACE === $tokens[$beforeArrowPtr]['code']) {
                $phpcsFile->fixer->replaceToken($beforeArrowPtr, \str_repeat(' ', $correctIndent));
            } else {
                $phpcsFile->fixer->addContent($beforeArrowPtr, \str_repeat(' ', $correctIndent));
            }

            $phpcsFile->fixer->endChangeset();
        }
    }
}

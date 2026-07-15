<?php

namespace App\Support;

use RuntimeException;

/**
 * Syntax highlighter behind the demo pages' "here is the actual source" panels.
 *
 * PHP runs through token_get_all(). Blade is segmented into comments, echoes and
 * directives, with the leftover markup highlighted as HTML and the expressions inside
 * {{ }} / @if(...) handed back to the PHP path.
 *
 * Every emitted value goes through escape(), so the returned string is safe to render
 * with {!! !!}. Nothing here picks colours — tokens only get tok-* class names; the
 * palette lives in resources/views/components/source-code.blade.php.
 */
class SourceHighlighter
{
    /** Reserved words that read as keywords rather than identifiers. */
    private const KEYWORDS = [
        T_ABSTRACT, T_AS, T_BREAK, T_CALLABLE, T_CASE, T_CATCH, T_CLASS, T_CLONE,
        T_CONST, T_CONTINUE, T_DECLARE, T_DEFAULT, T_DO, T_ECHO, T_ELSE, T_ELSEIF,
        T_EMPTY, T_ENDDECLARE, T_ENDFOR, T_ENDFOREACH, T_ENDIF, T_ENDSWITCH,
        T_ENDWHILE, T_ENUM, T_EXIT, T_EXTENDS, T_FINAL, T_FINALLY, T_FN, T_FOR,
        T_FOREACH, T_FUNCTION, T_GLOBAL, T_GOTO, T_IF, T_IMPLEMENTS, T_INCLUDE,
        T_INCLUDE_ONCE, T_INSTANCEOF, T_INSTEADOF, T_INTERFACE, T_ISSET, T_LIST,
        T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR, T_MATCH, T_NAMESPACE, T_NEW,
        T_PRINT, T_PRIVATE, T_PROTECTED, T_PUBLIC, T_READONLY, T_REQUIRE,
        T_REQUIRE_ONCE, T_RETURN, T_STATIC, T_SWITCH, T_THROW, T_TRAIT, T_TRY,
        T_UNSET, T_USE, T_VAR, T_WHILE, T_YIELD, T_YIELD_FROM,
        T_ARRAY_CAST, T_BOOL_CAST, T_DOUBLE_CAST, T_INT_CAST, T_OBJECT_CAST,
        T_STRING_CAST, T_UNSET_CAST,
    ];

    /** Bare identifiers that name a type here rather than a call. */
    private const TYPES = [
        'array', 'bool', 'callable', 'float', 'int', 'iterable', 'mixed', 'never',
        'object', 'parent', 'self', 'static', 'string', 'void',
    ];

    /**
     * Read a project file and highlight it by extension.
     *
     * @param  string  $path  Project-relative, e.g. app/Livewire/BookingEntry.php.
     * @param  string|null  $label  Tab caption; defaults to the filename.
     *
     * @throws RuntimeException When the path escapes the project or does not exist.
     */
    public static function file(string $path, ?string $label = null): HighlightedSource
    {
        $root = realpath(base_path());
        $full = realpath(base_path($path));

        // Not a sanitiser: every file in the project passes this, .env and the sqlite
        // database included. Both callers pass hardcoded literals, and this only makes a
        // typo'd or escaping path fail loudly rather than render something unintended.
        // Wiring $path to a request parameter would need an allowlist instead.
        if ($root === false || $full === false || ! is_file($full)
            || ! str_starts_with($full, $root.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("Source [{$path}] does not resolve to a file inside the project.");
        }

        // The trailing newline every file ends with would otherwise buy a blank last line.
        $code = rtrim((string) file_get_contents($full), "\n");

        return new HighlightedSource(
            label: $label ?? basename($full),
            path: $path,
            html: str_ends_with($full, '.blade.php') ? self::blade($code) : self::php($code),
            lines: substr_count($code, "\n") + 1,
        );
    }

    /** Highlight a complete PHP file (opening tag and all). */
    public static function php(string $code): string
    {
        return self::renderTokens(token_get_all($code));
    }

    /** Highlight a Blade template. */
    public static function blade(string $code): string
    {
        // {{-- comment --}} | {!! raw !!} | {{ echo }} | @directive with balanced args.
        //
        // The lookahead keeps Alpine/Livewire event bindings out: @click="save()" and
        // @click.prevent="…" are attributes, not directives, and a real directive is
        // never followed by "=". Without it they would be eaten here and the tag around
        // them would never reach html() intact.
        $pattern = '/\{\{--.*?--\}\}|\{!!.*?!!\}|\{\{.*?\}\}'
            .'|@[a-zA-Z]\w*(?![\w.:-]*=)(?:\s*(?P<args>\((?:[^()\'"]++|\'[^\']*\'|"[^"]*"|(?P>args))*\)))?/s';

        return self::scan(
            $code,
            $pattern,
            static fn (array $match): string => self::bladeToken($match[0][0]),
            static fn (string $gap): string => self::html($gap),
        );
    }

    /** One Blade construct: a comment, an echo, or a directive. */
    private static function bladeToken(string $text): string
    {
        if (str_starts_with($text, '{{--')) {
            return self::span('tok-comment', $text);
        }

        if (str_starts_with($text, '{!!')) {
            return self::echoBlock($text, 3);
        }

        if (str_starts_with($text, '{{')) {
            return self::echoBlock($text, 2);
        }

        return self::directive($text);
    }

    /** An echo: colour the delimiters, send what they wrap through the PHP path. */
    private static function echoBlock(string $text, int $width): string
    {
        return self::span('tok-blade', substr($text, 0, $width))
            .self::expression(substr($text, $width, -$width))
            .self::span('tok-blade', substr($text, -$width));
    }

    /** A directive: @name, plus its argument expression when it has one. */
    private static function directive(string $text): string
    {
        if (! preg_match('/^(@\w+)(\s*)(?:\((.*)\))?$/s', $text, $match)) {
            return self::span('tok-blade', $text);
        }

        $out = self::span('tok-blade', $match[1]).self::escape($match[2]);

        if (isset($match[3])) {
            $out .= self::span('tok-punct', '(')
                .self::expression($match[3])
                .self::span('tok-punct', ')');
        }

        return $out;
    }

    /** Highlight a bare PHP expression — one with no opening tag of its own. */
    private static function expression(string $expression): string
    {
        $tokens = token_get_all('<?php '.$expression);
        array_shift($tokens); // drop the synthetic opening tag

        return self::renderTokens($tokens);
    }

    /**
     * @param  list<array{int, string, int}|string>  $tokens
     */
    private static function renderTokens(array $tokens): string
    {
        $out = '';

        foreach ($tokens as $i => $token) {
            $out .= self::span(
                self::classFor($tokens, $i),
                is_array($token) ? $token[1] : $token,
            );
        }

        return $out;
    }

    /**
     * @param  list<array{int, string, int}|string>  $tokens
     */
    private static function classFor(array $tokens, int $i): string
    {
        $token = $tokens[$i];

        if (is_string($token)) {
            return 'tok-punct';
        }

        [$id, $text] = $token;

        return match (true) {
            $id === T_WHITESPACE => '',
            $id === T_INLINE_HTML => 'tok-html',
            $id === T_VARIABLE => 'tok-var',
            $id === T_ARRAY => 'tok-type',      // only ever a type hint in modern code
            $id === T_ATTRIBUTE => 'tok-attr',  // the "#[" opening #[Title('Resorts')]
            $id === T_STRING => self::classForIdentifier($tokens, $i, $text),
            in_array($id, [T_COMMENT, T_DOC_COMMENT], true) => 'tok-comment',
            in_array($id, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG], true) => 'tok-tag',
            // The heredoc delimiters belong to the string they fence, not to punctuation.
            in_array($id, [
                T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE,
                T_START_HEREDOC, T_END_HEREDOC,
            ], true) => 'tok-str',
            in_array($id, [T_LNUMBER, T_DNUMBER], true) => 'tok-num',
            in_array($id, [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true) => 'tok-class',
            in_array($id, self::KEYWORDS, true) => 'tok-kw',
            default => 'tok-punct',
        };
    }

    /**
     * A bare identifier is only classifiable by what sits either side of it.
     *
     * @param  list<array{int, string, int}|string>  $tokens
     */
    private static function classForIdentifier(array $tokens, int $i, string $text): string
    {
        $prev = self::neighbour($tokens, $i, -1);
        $next = self::neighbour($tokens, $i, 1);
        $lower = strtolower($text);

        return match (true) {
            $prev === '#[' => 'tok-attr',                            // the attribute's own name
            strtolower($prev) === 'new' => 'tok-class',              // before the call test: new Foo() names a class
            $next === '(' => 'tok-fn',
            $next === '::' => 'tok-class',
            $next === ':' && in_array($prev, ['(', ','], true) => 'tok-arg',  // named argument
            in_array($lower, ['true', 'false', 'null'], true) => 'tok-const',
            in_array($lower, self::TYPES, true) => 'tok-type',
            in_array($prev, ['->', '?->'], true) => 'tok-prop',
            // A ClassName carries lowercase; SCREAMING_CASE does not. That case difference
            // is the only thing separating Resort from JSON_PRETTY_PRINT at this point.
            ctype_upper($text[0]) && $text !== strtoupper($text) => 'tok-class',
            default => 'tok-const',
        };
    }

    /**
     * Text of the nearest token in $step's direction, skipping whitespace and comments.
     *
     * @param  list<array{int, string, int}|string>  $tokens
     */
    private static function neighbour(array $tokens, int $i, int $step): string
    {
        for ($j = $i + $step; isset($tokens[$j]); $j += $step) {
            $token = $tokens[$j];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) ? $token[1] : $token;
        }

        return '';
    }

    /** Markup outside any Blade construct: tag names, attributes, quoted values. */
    private static function html(string $html): string
    {
        // Quoted values are consumed whole, so a ">" inside one cannot close the tag.
        $pattern = '/<!--.*?-->|(<\/?)([a-zA-Z][\w:.-]*)((?:"[^"]*"|\'[^\']*\'|[^>"\'])*?)(\/?>)/s';

        return self::scan(
            $html,
            $pattern,
            static function (array $match): string {
                $open = self::group($match, 1);

                if ($open === null) {
                    return self::span('tok-comment', $match[0][0]); // <!-- … -->
                }

                return self::span('tok-punct', $open)
                    .self::span('tok-tag', (string) self::group($match, 2))
                    .self::attributes((string) self::group($match, 3))
                    .self::span('tok-punct', (string) self::group($match, 4));
            },
            static fn (string $gap): string => self::escape($gap),
        );
    }

    /** The inside of a tag: name[="value"] pairs, plus bare attributes like data-save. */
    private static function attributes(string $text): string
    {
        $pattern = '/([\w:@.\-\[\]]+)(?:(\s*=\s*)("[^"]*"|\'[^\']*\'|[^\s>]+))?/';

        return self::scan(
            $text,
            $pattern,
            static function (array $match): string {
                $out = self::span('tok-attr', (string) self::group($match, 1));
                $equals = self::group($match, 2);

                // A bare attribute — data-save, wire:ignore — carries no value to colour.
                if ($equals === null) {
                    return $out;
                }

                return $out
                    .self::span('tok-punct', $equals)
                    .self::span('tok-str', (string) self::group($match, 3));
            },
            static fn (string $gap): string => self::escape($gap),
        );
    }

    /**
     * Text of capture group $i, or null when it did not participate. Guards two PCRE
     * shapes at once: a skipped group in the middle reports offset -1, while trailing
     * skipped groups are left out of the match set entirely.
     *
     * @param  array<array-key, array{string, int}>  $match
     */
    private static function group(array $match, int $i): ?string
    {
        return isset($match[$i]) && $match[$i][1] !== -1 ? $match[$i][0] : null;
    }

    /**
     * Walk $pattern across $subject, handing each match to $onMatch and the text between
     * matches to $onGap. Both callbacks own the escaping of what they emit.
     *
     * @param  callable(array<array-key, array{string, int}>): string  $onMatch
     * @param  callable(string): string  $onGap
     */
    private static function scan(string $subject, string $pattern, callable $onMatch, callable $onGap): string
    {
        preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $out = '';
        $cursor = 0;

        foreach ($matches as $match) {
            [$text, $offset] = $match[0];

            if ($offset > $cursor) {
                $out .= $onGap(substr($subject, $cursor, $offset - $cursor));
            }

            $out .= $onMatch($match);
            $cursor = $offset + strlen($text);
        }

        return $out.$onGap(substr($subject, $cursor));
    }

    private static function span(string $class, string $text): string
    {
        if ($text === '') {
            return '';
        }

        return $class === ''
            ? self::escape($text)
            : '<span class="'.$class.'">'.self::escape($text).'</span>';
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

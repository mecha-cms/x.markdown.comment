<?php namespace x;

function markdown__comment($any) {
    foreach ($_POST['comment'] ?? [] as $k => $v) {
        if (!\is_string($v)) {
            continue;
        }
        // It is not possible to filter-out code block inside list item or block quote currently
        $v = \n($v); // Normalize line-break
        if (
            false !== \strpos("\n" . $v, "\n```") ||
            false !== \strpos("\n" . $v, "\n~~~") ||
            false !== \strpos("\n" . $v, "\n    ")
        ) {
            $parts = \explode("\n", $v);
            $blocks = [];
            $id = 0;
            $key = false;
            foreach ($parts as $part) {
                if ($key && ('`' === $key[0] || '~' === $key[0])) {
                    if ($key === $part) {
                        $key = false;
                    }
                    // Code block close or code block continue
                    $blocks[$id - 1] .= "\n" . $part;
                    continue;
                }
                // Fence code block style
                if (0 === \strpos($part, '```') || 0 === \strpos($part, '~~~')) {
                    $key = \substr($part, 0, \strspn($part, '`~'));
                    // Code block open
                    $blocks[$id] = $part;
                }
                // Dent code block style
                if (0 === \strpos($part, '    ')) {
                    // Code block sequence
                    $blocks[$id] = $part;
                // Other block(s)
                } else {
                    // We are now outside the code block!
                    $part = \preg_replace_callback('/`[^\n]+`|<(?:f|ht)tps?:\/\/[^\n]+?>/', function($m) {
                        return \htmlspecialchars(\preg_replace('/\s+/', ' ', $m[0]));
                    }, $part);
                    $part = \strip_tags($part); // Remove HTML tag(s)
                    $part = \preg_replace_callback('/`[^\n]+`|&lt;(?:f|ht)tps?:\/\/[^\n]+?&gt;/', function($m) {
                        return \htmlspecialchars_decode($m[0]);
                    }, $part);
                    $blocks[$id] = $part;
                }
                ++$id;
            }
            $v = \implode("\n", $blocks);
        } else {
            $v = \strip_tags($v);
        }
        $_POST['comment'][$k] = $v;
    }
    // Force comment type to `Markdown`
    $_POST['comment']['type'] = 'Markdown';
}

\Route::hit('.comment/*', __NAMESPACE__ . "\\markdown__comment", 0);

// Optional `comment.hint` extension
if (null !== \State::get("x.comment\\.hint")) {
    \State::set("x.comment\\.hint.content", 'All HTML tags will be removed. Use <a href="https://mecha-cms.com/article/markdown-syntax" target="_blank">Markdown</a> syntax to style your comment body.');
}

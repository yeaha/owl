<?php
defined('DEBUG') or define('DEBUG', false);
defined('TEST') or define('TEST', false);

// /?
// \w+                               tag name
// (?:                               attributes
//     (?:
//         \s+
//         [\w\-\.:]+                attribute key
//         (?:
//             \s*=\s*
//             ["\']?                quote symbol
//             (?:
//                 [^"\'>]+          attribute value
//             )?
//             ["\']?                quote symbol
//         )?
//     )?
// )+
// \s*
// /?
// \s*
defined('TAGS_REGEXP') or define('TAGS_REGEXP', '#</?\w+(?:(?:\s+[\w\-\.:]+(?:\s*=\s*["\']?(?:[^"\'>]+)?["\']?)?)?)+\s*/?\s*>#');

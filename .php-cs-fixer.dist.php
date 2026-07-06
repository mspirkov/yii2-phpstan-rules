<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer;
use PhpCsFixerCustomFixers\Fixer\MultilineCommentOpeningClosingAloneFixer;
use PhpCsFixerCustomFixers\Fixer\NoPhpStormGeneratedCommentFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocTypesCommaSpacesFixer;
use PhpCsFixerCustomFixers\Fixer\PhpdocTypesTrimFixer;
use PhpCsFixerCustomFixers\Fixers;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude([
        'tests/Rules/Data',
        'tests/Rules/Source'
    ]);

return (new Config())
    ->registerCustomFixers(new Fixers())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PHP7x4Migration' => true,
        '@PER-CS3x0' => true,
        'align_multiline_comment' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'continue',
                'declare',
                'return',
                'throw',
            ],
        ],
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'class_reference_name_casing' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'declare_parentheses' => true,
        'declare_strict_types' => true,
        'echo_tag_syntax' => [
            'format' => 'short',
        ],
        'explicit_string_variable' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true
        ],
        'global_namespace_import' => true,
        'include' => true,
        'lambda_not_used_import' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'multiline_whitespace_before_semicolons' => true,
        'native_function_casing' => true,
        'native_type_declaration_casing' => true,
        'no_alternative_syntax' => true,
        'no_alias_language_construct_call' => true,
        'no_empty_comment' => true,
        'no_empty_statement' => true,
        'no_empty_phpdoc' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'parenthesis_brace_block',
                'square_brace_block',
            ],
        ],
        'no_leading_namespace_whitespace' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_mixed_echo_print' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
        ],
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_import_alias' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_concat_operator' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'nullable_type_declaration' => [
            'syntax' => 'question_mark',
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'object_operator_without_whitespace' => true,
        'ordered_class_elements' => true,
        'php_unit_data_provider_method_order' => true,
        'php_unit_method_casing' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,
        'protected_to_private' => true,
        'return_assignment' => [
            'skip_named_var_tags' => true,
        ],
        'self_static_accessor' => true,
        'semicolon_after_instruction' => true,
        'simple_to_complex_string_variable' => true,
        'single_class_element_per_statement' => true,
        'single_line_comment_spacing' => true,
        'single_line_comment_style' => [
            'comment_types' => [
                'hash',
            ],
        ],
        'single_quote' => true,
        'space_after_semicolon' => true,
        'standardize_increment' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'type_declaration_spaces' => [
            'elements' => [
                'constant',
                'function',
                'property',
            ],
        ],
        'types_spaces' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'whitespace_after_comma_in_array' => [
            'ensure_single_space' => true,
        ],
        CommentSurroundedBySpacesFixer::name() => true,
        MultilineCommentOpeningClosingAloneFixer::name() => true,
        NoPhpStormGeneratedCommentFixer::name() => true,
        PhpdocTypesCommaSpacesFixer::name() => true,
        PhpdocTypesTrimFixer::name() => true,
    ]);

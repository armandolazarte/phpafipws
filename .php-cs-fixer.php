<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__) // Busca archivos PHP en el directorio actual y subdirectorios
    ->exclude('vendor') // Excluye el directorio 'vendor' para no formatear dependencias
    ->name('*.php') // Asegura que solo se consideren archivos .php
    ->ignoreDotFiles(true) // Ignora archivos ocultos (como .git, .editorconfig)
    ->ignoreVCS(true); // Ignora archivos de sistemas de control de versiones

return (new PhpCsFixer\Config)
    // Permite la ejecución en versiones de PHP no soportadas oficialmente (como PHP 8.4.8).
    // Nota: Esto puede llevar a inestabilidad o modificaciones incorrectas del código.
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        // Reglas directas del preset de Laravel Pint con comentarios explicativos:
        'array_indentation' => true, // Asegura que los elementos de los arrays multilínea estén correctamente indentados.
        'array_syntax' => ['syntax' => 'short'], // Fuerza el uso de la sintaxis corta para arrays (ej. `[]` en lugar de `array()`).
        'binary_operator_spaces' => [
            'default' => 'single_space', // Asegura un solo espacio alrededor de los operadores binarios (ej. `$a = $b;`).
        ],
        'blank_line_after_namespace' => true, // Fuerza una línea en blanco después de la declaración del namespace.
        'blank_line_after_opening_tag' => true, // Asegura una línea en blanco después de la etiqueta de apertura PHP (`<?php`).
        'blank_line_before_statement' => [
            'statements' => [
                'continue',
                'return',
            ],
        ], // Fuerza una línea en blanco antes de `continue` y `return` (y otros si se añaden).
        'blank_line_between_import_groups' => true, // Añade una línea en blanco entre grupos de declaraciones `use`.
        'blank_lines_before_namespace' => true, // Fuerza una línea en blanco antes de la declaración del namespace.
        'braces_position' => [
            'control_structures_opening_brace' => 'same_line', // `{` para estructuras de control en la misma línea.
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end', // `{` para funciones en nueva línea, a menos que la firma termine en nueva línea.
            'anonymous_functions_opening_brace' => 'same_line', // `{` para funciones anónimas en la misma línea.
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end', // `{` para clases en nueva línea, a menos que la firma termine en nueva línea.
            'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end', // `{` para clases anónimas en nueva línea, a menos que la firma termine en nueva línea.
            'allow_single_line_empty_anonymous_classes' => false, // No permite clases anónimas vacías en una sola línea.
            'allow_single_line_anonymous_functions' => false, // No permite funciones anónimas en una sola línea.
        ],
        'cast_spaces' => true, // Elimina el espacio entre un cast y la variable (ej. `(int)$var` en lugar de `(int) $var`).
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one', // Una línea en blanco entre constantes de clase.
                'method' => 'one', // Una línea en blanco entre métodos de clase.
                'property' => 'one', // Una línea en blanco entre propiedades de clase.
                'trait_import' => 'none', // Sin línea en blanco después de los `use` de traits.
                'case' => 'none', // Sin línea en blanco entre casos de enum.
            ],
        ],
        'class_definition' => [
            'multi_line_extends_each_single_line' => true, // Cada extensión/implementación en una nueva línea si son múltiples.
            'single_item_single_line' => true, // Si solo hay un elemento en la definición, puede estar en una sola línea.
            'single_line' => true, // Permite clases como `class Foo extends Bar {}` en una sola línea si son cortas y vacías.
        ],
        'clean_namespace' => true, // Elimina los `use` duplicados o no usados en el mismo `namespace`.
        'compact_nullable_type_declaration' => true, // Elimina el espacio entre `?` y el tipo (ej. `?string` en lugar de `? string`).
        'concat_space' => [
            'spacing' => 'none', // No añade espacios alrededor del operador de concatenación (`.`).
        ],
        'constant_case' => ['case' => 'lower'], // Convierte los `true`, `false`, `null` a minúsculas.
        'control_structure_braces' => true, // Garantiza que las llaves de las estructuras de control (`if`, `for`, etc.) estén correctamente formateadas.
        'control_structure_continuation_position' => [
            'position' => 'same_line', // Posiciona `else`, `elseif`, `finally` en la misma línea que la llave de cierre anterior.
        ],
        'declare_equal_normalize' => true, // Normaliza el espacio alrededor del signo `=` en las declaraciones `declare`.
        'declare_parentheses' => true, // Asegura paréntesis alrededor del valor en `declare` (ej. `declare(strict_types=1)`).
        'elseif' => true, // Convierte `else if` a `elseif`.
        'encoding' => true, // Elimina el byte order mark (BOM) si está presente.
        'full_opening_tag' => true, // Asegura que siempre se use la etiqueta de apertura completa (`<?php`).
        'fully_qualified_strict_types' => true, // Asegura que las declaraciones de tipo (en argumentos, retornos, propiedades) utilicen nombres de clase completamente calificados (con barra invertida inicial, ej. `\App\Models\User`).
        'function_declaration' => true, // Formatea la declaración de funciones para que las llaves estén en la misma línea y los parámetros tengan un solo espacio.
        'general_phpdoc_tag_rename' => true, // Renombra etiquetas de PHPDoc antiguas a las nuevas (ej. `@api` a `@internal`).
        'heredoc_to_nowdoc' => true, // Convierte heredocs que no tienen interpolación de variables a nowdocs.
        'include' => true, // Normaliza los espacios alrededor de las sentencias `include`/`require`.
        'increment_style' => ['style' => 'post'], // Usa el estilo de incremento/decremento post-fijo (ej. `$i++`).
        'indentation_type' => true, // Fuerza el uso de espacios para la indentación.
        'integer_literal_case' => true, // Normaliza el uso de mayúsculas/minúsculas para literales enteros hexadecimales o binarios.
        'lambda_not_used_import' => true, // Elimina variables no usadas en la cláusula `use` de las funciones anónimas.
        'line_ending' => true, // Fuerza el uso de saltos de línea de estilo Unix (`\n`).
        'linebreak_after_opening_tag' => true, // Garantiza un salto de línea después de la etiqueta de apertura `<?php`.
        'list_syntax' => true, // Fuerza el uso de la sintaxis corta para `list()` (ej. `[$a, $b] = ...`).
        'lowercase_cast' => true, // Convierte los casts de tipo a minúsculas (ej. `(array)$var`).
        'lowercase_keywords' => true, // Convierte las palabras clave de PHP a minúsculas (ej. `function`, `class`).
        'lowercase_static_reference' => true, // Convierte `self`, `parent`, `static` a minúsculas cuando se usan como referencias estáticas.
        'magic_constant_casing' => true, // Asegura que las constantes mágicas (ej. `__FILE__`) estén en mayúsculas.
        'magic_method_casing' => true, // Asegura que los nombres de métodos mágicos (ej. `__construct`) estén en minúsculas.
        'method_argument_space' => [
            'on_multiline' => 'ignore', // Ignora el espaciado de argumentos en múltiples líneas.
        ],
        'method_chaining_indentation' => true, // Aplica indentación en cadenas de métodos.
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line', // Asegura que no haya espacio en blanco antes del punto y coma en líneas múltiples.
        ],
        'native_function_casing' => true, // Asegura que las funciones nativas de PHP estén en minúsculas (ej. `strlen`).
        'native_type_declaration_casing' => true, // Asegura que las declaraciones de tipo nativo estén en minúsculas (ej. `string`, `int`).
        'new_with_parentheses' => [
            'named_class' => false, // No fuerza paréntesis al instanciar clases nombradas (ej. `new MyClass`).
            'anonymous_class' => false, // No fuerza paréntesis al instanciar clases anónimas.
        ],
        'no_alias_functions' => true, // Reemplaza alias de funciones con sus nombres reales (ej. `is_null` por `null ===`).
        'no_alias_language_construct_call' => true, // Reemplaza alias de constructos de lenguaje (ej. `die` por `exit`).
        'no_alternative_syntax' => true, // Elimina la sintaxis alternativa de estructuras de control (ej. `endif;` a `}`).
        'no_binary_string' => true, // Elimina el prefijo `b` de las cadenas binarias si no es necesario (PHP 7.0+).
        'no_blank_lines_after_class_opening' => true, // Elimina líneas en blanco después de la llave de apertura de una clase.
        'no_blank_lines_after_phpdoc' => true, // Elimina líneas en blanco después de un bloque PHPDoc.
        'no_closing_tag' => true, // Elimina la etiqueta de cierre en archivos PHP puros.
        'no_empty_phpdoc' => true, // Elimina bloques PHPDoc vacíos.
        'no_empty_statement' => true, // Elimina sentencias vacías.
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra', // Elimina líneas en blanco adicionales.
                'throw', // Elimina líneas en blanco antes de `throw`.
                'use', // Elimina líneas en blanco antes de declaraciones `use`.
            ],
        ],
        'no_leading_import_slash' => true, // Elimina la barra invertida inicial de las declaraciones `use`.
        'no_leading_namespace_whitespace' => true, // Elimina espacios en blanco antes de la declaración del namespace.
        'no_mixed_echo_print' => [
            'use' => 'echo', // Fuerza el uso de `echo` en lugar de `print`.
        ],
        'no_multiline_whitespace_around_double_arrow' => true, // Elimina el espacio en blanco de varias líneas alrededor del operador `=>` en arrays.
        'no_multiple_statements_per_line' => true, // Asegura una sola sentencia por línea.
        'no_short_bool_cast' => true, // Reemplaza `(bool)` cast con `!!`.
        'no_singleline_whitespace_before_semicolons' => true, // Elimina el espacio en blanco de una sola línea antes de un punto y coma.
        'no_space_around_double_colon' => true, // Elimina el espacio alrededor del operador `::` (ej. `Foo:: bar` a `Foo::bar`).
        'no_spaces_after_function_name' => true, // Elimina espacios después del nombre de la función y antes del paréntesis.
        'no_spaces_around_offset' => [
            'positions' => ['inside', 'outside'], // Elimina espacios alrededor de los corchetes de array/cadena (ej. `$array [ 'key' ]` a `$array['key']`).
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true, // No elimina `@param mixed` o `@return mixed`.
            'allow_unused_params' => true, // No elimina `@param` para parámetros no usados.
        ],
        'no_trailing_comma_in_singleline' => true, // Elimina la coma final en arrays de una sola línea.
        'no_trailing_whitespace' => true, // Elimina el espacio en blanco al final de las líneas.
        'no_trailing_whitespace_in_comment' => true, // Elimina el espacio en blanco al final de los comentarios.
        'no_unneeded_control_parentheses' => [
            'statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield'],
        ], // Elimina paréntesis innecesarios en ciertas sentencias.
        'no_unneeded_braces' => true, // Elimina llaves innecesarias alrededor de sentencias simples.
        'no_unneeded_import_alias' => true, // Elimina alias innecesarios en declaraciones `use`.
        'no_unreachable_default_argument_value' => true, // Elimina valores predeterminados para argumentos que nunca son alcanzados (debido a argumentos opcionales anteriores).
        'no_unset_cast' => true, // Elimina `(unset)` cast.
        'no_unused_imports' => true, // Elimina declaraciones `use` no utilizadas.
        'no_useless_return' => true, // Elimina sentencias `return` inútiles al final de una función que no devuelve nada.
        'no_whitespace_before_comma_in_array' => true, // Elimina el espacio en blanco antes de la coma en los arrays.
        'no_whitespace_in_blank_line' => true, // Elimina cualquier espacio en blanco en líneas completamente en blanco.
        'normalize_index_brace' => true, // Normaliza el uso de corchetes para acceder a índices (ej. `array[0]` a `array[0]`).
        'not_operator_with_successor_space' => true, // Asegura un espacio después del operador `!`.
        'nullable_type_declaration' => true, // Asegura el espaciado correcto para declaraciones de tipo anulable.
        'nullable_type_declaration_for_default_null_value' => true, // Añade el `?` a los tipos de parámetro con valor predeterminado `null`.
        'object_operator_without_whitespace' => true, // Elimina el espacio alrededor del operador de objeto `->`.
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['const', 'class', 'function']], // Ordena alfabéticamente las declaraciones `use`, agrupando por tipo.
        'ordered_interfaces' => true, // Ordena las interfaces implementadas alfabéticamente.
        'php_unit_method_casing' => ['case' => 'snake_case'], // Fuerza `snake_case` para los nombres de métodos de prueba de PHPUnit.
        'php_unit_set_up_tear_down_visibility' => true, // Asegura que los métodos `setUp()` y `tearDown()` de PHPUnit sean `protected`.
        'phpdoc_align' => [
            'align' => 'left', // Alinea las descripciones de PHPDoc a la izquierda.
            'spacing' => [
                'param' => 2, // Espacio para el @param en PHPDoc.
            ],
        ],
        'phpdoc_indent' => true, // Aplica indentación a los comentarios PHPDoc.
        'phpdoc_inline_tag_normalizer' => true, // Normaliza el espaciado de etiquetas PHPDoc en línea (ej. `{@see}` a `{@see }`).
        'phpdoc_no_access' => true, // Elimina la etiqueta `@access` de PHPDoc.
        'phpdoc_no_package' => true, // Elimina la etiqueta `@package` de PHPDoc.
        'phpdoc_no_useless_inheritdoc' => true, // Elimina `{@inheritdoc}` si no es necesario (ej. si no hay descripción en el padre).
        'phpdoc_order' => [
            'order' => ['param', 'return', 'throws'], // Ordena las etiquetas PHPDoc en el orden especificado.
        ],
        'phpdoc_scalar' => true, // Normaliza los tipos escalares en PHPDoc (ej. `boolean` a `bool`).
        'phpdoc_separation' => [
            'groups' => [
                ['deprecated', 'link', 'see', 'since'], // Separa grupos de etiquetas PHPDoc con líneas en blanco.
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['property', 'property-read', 'property-write'],
                ['param', 'return'],
            ],
        ],
        'phpdoc_single_line_var_spacing' => true, // Normaliza el espaciado para variables de PHPDoc de una sola línea.
        'phpdoc_summary' => false, // **No** fuerza la existencia de un resumen en PHPDoc.
        'phpdoc_tag_type' => [
            'tags' => [
                'inheritdoc' => 'inline', // Trata `inheritdoc` como una etiqueta en línea.
            ],
        ],
        'phpdoc_to_comment' => false, // **No** convierte comentarios PHPDoc a comentarios normales.
        'phpdoc_trim' => true, // Recorta el espacio en blanco al inicio y al final de los comentarios PHPDoc.
        'phpdoc_types' => true, // Normaliza los tipos en PHPDoc (ej. `int[]` a `array<int>`).
        'phpdoc_var_without_name' => true, // Elimina el nombre de la variable en la etiqueta `@var` si no es necesario.
        'psr_autoloading' => false, // **No** fuerza los nombres de archivos y clases a cumplir con PSR-4.
        'return_type_declaration' => ['space_before' => 'none'], // Elimina el espacio antes de los dos puntos en las declaraciones de tipo de retorno (ej. `:string`).
        'self_static_accessor' => true, // Reemplaza `$this->` con `self::` o `static::` cuando sea apropiado para llamadas estáticas.
        'short_scalar_cast' => true, // Usa las formas cortas de cast (ej. `(int)` en lugar de `(integer)`).
        'simplified_null_return' => false, // **No** simplifica los retornos `return null;` a `return;` en funciones que devuelven `void`.
        'single_blank_line_at_eof' => true, // Asegura una sola línea en blanco al final del archivo.
        'single_class_element_per_statement' => [
            'elements' => ['const', 'property'], // Fuerza una constante o propiedad por declaración (no aplica a casos de enum).
        ],
        'single_import_per_statement' => true, // Asegura una sola declaración `use` por línea.
        'single_line_after_imports' => true, // Asegura una sola línea en blanco después del último bloque de importaciones.
        'single_line_comment_spacing' => true, // Asegura un espacio adecuado para los comentarios de una sola línea.
        'single_line_comment_style' => [
            'comment_types' => ['hash'], // Convierte comentarios con `#` a `//`.
        ],
        'single_line_empty_body' => true, // Permite un cuerpo de sentencia vacío en una sola línea (ej. `if ($a) {}`).
        'single_quote' => true, // Convierte cadenas con comillas dobles a comillas simples cuando sea posible.
        'single_space_around_construct' => true, // Asegura un solo espacio alrededor de constructos de lenguaje (ej. `list`, `array`).
        'space_after_semicolon' => true, // Añade un espacio después del punto y coma.
        'spaces_inside_parentheses' => true, // Elimina espacios dentro de los paréntesis (ej. `( $a )` a `($a)`).
        'standardize_not_equals' => true, // Reemplaza `!=` con `<>` (o viceversa, según la configuración).
        'statement_indentation' => true, // Asegura que las sentencias estén correctamente indentadas dentro de las estructuras de control.
        'switch_case_semicolon_to_colon' => true, // Reemplaza punto y coma por dos puntos en los casos `switch` (ej. `case 1;` a `case 1:`).
        'switch_case_space' => true, // Asegura un espacio después de `case` y `default` en un `switch`.
        'ternary_operator_spaces' => true, // Asegura un espacio alrededor de los operadores ternarios.
        'trailing_comma_in_multiline' => ['elements' => ['arrays']], // Añade una coma final en arrays multilínea.
        'trim_array_spaces' => true, // Recorta el espacio en blanco dentro de los corchetes de los arrays.
        'type_declaration_spaces' => true, // Normaliza los espacios en las declaraciones de tipo.
        'types_spaces' => true, // Normaliza el espaciado en las declaraciones de tipo de parámetro/retorno.
        'unary_operator_spaces' => true, // Asegura un espacio adecuado alrededor de los operadores unarios.
        'visibility_required' => [
            'elements' => ['method', 'property'], // Fuerza la visibilidad (public, protected, private) para métodos y propiedades.
        ],
        'whitespace_after_comma_in_array' => true, // Asegura un espacio después de la coma en los elementos del array.
        'yoda_style' => [
            'always_move_variable' => false, // No mueve la variable en las comparaciones de estilo Yoda.
            'equal' => false, // No fuerza estilo Yoda para comparaciones de igualdad (ej. `true == $var`).
            'identical' => false, // No fuerza estilo Yoda para comparaciones de identidad (ej. `true === $var`).
            'less_and_greater' => false, // No fuerza estilo Yoda para comparaciones de menor/mayor.
        ],

        // Riesgosas (requieren --allow-risky=yes):
        'array_push' => true, // [Riesgosa] Reemplaza `array_push($array, $value)` por `$array[] = $value`.
        'backtick_to_shell_exec' => true, // [Riesgosa] Reemplaza el operador backtick (``) por `shell_exec()`.
        'date_time_immutable' => true, // [Riesgosa] Reemplaza `DateTime` por `DateTimeImmutable`.
        'declare_strict_types' => true, // [Riesgosa] Añade `declare(strict_types=1);` al inicio de los archivos.
        'final_internal_class' => true, // [Riesgosa] MAFIP como `final` las clases internas (no destinadas a herencia).
        'final_public_method_for_abstract_class' => true, // [Riesgosa] MAFIP los métodos públicos de clases abstractas como `final`.
        'mb_str_functions' => true, // [Riesgosa] Reemplaza las funciones de cadena normales por sus equivalentes `mb_str_` (multibyte).
        'modernize_types_casting' => true, // [Riesgosa] Moderniza los casts de tipo (ej. `(boolean)` a `(bool)`).
        'ordered_traits' => true, // [Riesgosa] Ordena alfabéticamente los `use` de traits.
        'self_accessor' => false, // [Riesgosa, Pint=false] Desactiva la regla que reemplaza `$this->` con `self::` para métodos estáticos (Pint la desactiva).
        'strict_comparison' => true, // [Riesgosa] Fuerza el uso de comparaciones estrictas (`===`, `!==`).

        // Reglas personalizadas de tu configuración de Pint:
        'final_class' => false, // No fuerza que todas las clases sean `final`.
        'global_namespace_import' => [ // Configura cómo se tratan las importaciones del espacio de nombres global.
            'import_classes' => true, // Importa clases del espacio de nombres global.
            'import_constants' => true, // Importa constantes del espacio de nombres global.
            'import_functions' => true, // Importa funciones del espacio de nombres global.
        ],
        'no_superfluous_elseif' => true, // Elimina `elseif` superfluos cuando un `if` ya maneja todos los casos.
        'no_useless_else' => true, // Elimina bloques `else` cuando no son necesarios (ej. si el `if` termina con `return` o `throw`).
        'ordered_class_elements' => [ // Define el orden de los elementos dentro de las clases.
            'order' => [
                'use_trait',
                'case',
                'constant',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_abstract',
                'method_public_static',
                'method_public',
                'method_protected_static',
                'method_protected',
                'method_private_static',
                'method_private',
            ],
            'sort_algorithm' => 'none', // No aplica un algoritmo de ordenación adicional (se basa en el `order` definido).
        ],
        'protected_to_private' => true, // Convierte propiedades y métodos `protected` a `private` si no son accedidos por clases hijas.
    ])
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache'); // Archivo de caché para mejorar el rendimiento

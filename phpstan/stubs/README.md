PHPStan-only stubs for functions the host environment provides at runtime
(theme helpers, other plugins). Never included at runtime. Guard every real
call with `function_exists()`.

PHPStan-only stubs for functions and classes the host environment provides at
runtime (theme helpers, other plugins). Never included at runtime. Guard
every real function call with `function_exists()`; guard every real class
use with `class_exists()` or a class-based inert check (see `Dam_Bridge`).

- `vip-dam.php`: the VIP DAM's public surface (`VIP\DAM\Embargo_Guard`,
  `VIP\DAM\Lifecycle`, `VIP\DAM\Usage_Index`), so `composer analyse` needs
  no DAM checkout, including in CI. See `bin/fetch-dam.sh` and §7.4.

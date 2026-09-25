# ADR 0001: Hand-wired composition root, no container

Status: accepted

## Decision

`Plugin` constructs every service in its private constructor, exposes one
accessor per service, and `replace()` swaps a service in tests only. There is
no DI container, no service locator, and no static singleton other than
`Keys`, `Hooks` and `Plugin::instance()`.

## Why

This plugin has a dozen services at most. A container adds a dependency and a
layer the reviewer cannot grep. Hand wiring keeps the dependency graph
readable in one file and makes "who may import whom" a mechanical check.

## Consequences

Adding a service touches `Plugin` in three places (constructor, accessor,
`replace()` case). That is deliberate: the composition root is the module
map, and it should change when the map changes.

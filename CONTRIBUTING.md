# CONTRIBUCIONES

Las contribuciones son bienvenidas y se aceptan a través de pull requests. Por favor, revisa estas directrices antes de enviar cualquier pull request.

---

## Proceso

1.  Haz un "fork" del proyecto
2.  Crea una nueva rama
3.  Codifica, prueba, haz "commit" y "push"
4.  Abre un "pull request" detallando tus cambios. Asegúrate de seguir la [plantilla](.github/PULL_REQUEST_TEMPLATE.md)

---

## Directrices

-   Por favor, asegúrate de que el estilo de codificación sea el correcto ejecutando `composer lint`.
-   Envía un historial de "commits" coherente, asegurándote de que cada "commit" individual en tu "pull request" sea significativo.
-   Puede que necesites hacer un "[rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing)" para evitar conflictos de fusión.
-   Por favor, recuerda que seguimos [SemVer](https://semver.org/lang/es/).

---

## Configuración

Clona tu "fork" y luego instala las dependencias de desarrollo:

```bash
composer install
```

## Lint

Revisa tu código:

```bash
composer lint
```

## Pruebas

Ejecuta todas las pruebas:

```bash
composer test
```

Verifica los tipos:

```bash
composer test:types
```

Pruebas unitarias:

```bash
composer test:unit
```

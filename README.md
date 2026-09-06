# Symfony LSP : bac à sable

Application Symfony 8.1 servant de terrain d'expérimentation à
[Symfony Language Tools](https://github.com/symfony/language-tools), le serveur
LSP officiel de Symfony, utilisé ici **en ligne de commande** plutôt que dans un
éditeur.

Ce dépôt accompagne une vidéo YouTube. Le projet est un `symfony new --webapp`
standard, auquel **cinq fichiers** seulement ont été ajoutés : juste ce qu'il
faut pour produire quatre familles d'erreurs que PHPStan ne peut pas voir.

## Ce que le dépôt démontre

PHPStan analyse le code PHP. Il ne connaît ni les noms de routes, ni les chemins
de templates, ni les clés de traduction, ni les options de formulaire : ces
informations n'existent pas dans le code, elles vivent dans le conteneur compilé
de l'application.

`symfony lsp:check` boote l'application et lit ces métadonnées réelles. Il voit
donc une catégorie d'erreurs qu'aucun analyseur statique ne peut voir.

## Les deux branches

| Branche  | Contenu | PHPStan (niveau max) | `symfony lsp:check` |
|----------|---------|----------------------|---------------------|
| `main`   | Application correcte | ✅ vert | ✅ vert, 0 diagnostic |
| `broken` | 6 familles d'erreurs Symfony | ✅ **vert** | ❌ rouge, 8 diagnostics |

La ligne qui compte est la seconde : **PHPStan reste vert alors que
l'application est cassée.** Il tourne pourtant au niveau maximum, avec
l'extension officielle `phpstan-symfony` et le conteneur compilé sous les yeux.

## Les cinq fichiers ajoutés

Tout le reste est le squelette généré par `symfony new --webapp`.

```
src/Controller/CheckoutController.php   2 routes, 1 render, 1 redirectToRoute
src/Form/CheckoutType.php               2 champs de formulaire
templates/checkout.html.twig            path(), trans, include (9 lignes, 4 erreurs)
templates/summary.html.twig             la cible de l'include
translations/messages.en.yaml           le catalogue
```

## Les erreurs présentes sur `broken`

| Diagnostic | Emplacement | Conséquence réelle |
|------------|-------------|--------------------|
| `route.not_found` | `checkout.html.twig:7` et `CheckoutController:23` | erreur 500 |
| `template.not_found` | `checkout.html.twig:5` et `CheckoutController:15` | erreur 500 |
| `translation.not_found` | `checkout.html.twig:1` et `:7` | la clé brute s'affiche en production |
| `form.unknown_option` | `CheckoutType:18` et `:19` | option silencieusement refusée |

## Reproduire en local

Prérequis : PHP 8.4+, Composer, et Symfony CLI 5.20 ou supérieur, qui expose la
commande `symfony lsp:check`.

```bash
git clone https://github.com/yoanbernabeu/symfony-lsp-demo.git
cd symfony-lsp-demo
composer install
php bin/console cache:warmup --env=dev
```

Aucune base de données n'est nécessaire : l'application n'en utilise pas, et
`symfony lsp:check` se contente de la démarrer pour lire ses métadonnées.

Sur `main`, les deux outils sont verts :

```bash
vendor/bin/phpstan analyse    # [OK] No errors
symfony lsp:check             # 0 diagnostic, code de sortie 0
```

Sur `broken`, seul le LSP réagit :

```bash
git switch broken
php bin/console cache:warmup --env=dev
vendor/bin/phpstan analyse    # [OK] No errors      <- toujours vert
symfony lsp:check             # 8 diagnostics       <- code de sortie 10
```

## Codes de sortie

`symfony lsp:check` renvoie des codes stables, ce qui le rend scriptable :

| Code | Signification |
|------|---------------|
| `0`  | aucun diagnostic bloquant |
| `10` | diagnostics bloquants |
| `11` | invocation ou configuration invalide |
| `12` | analyse incomplète (indexation, timeout, échec de processus) |

## Intégration continue

Le workflow [`.github/workflows/ci.yaml`](.github/workflows/ci.yaml) lance les
deux outils sur chaque branche. Sur `broken`, le job PHPStan passe et le job
Symfony LSP échoue, avec les diagnostics annotés directement dans les fichiers.

## Précisions

- Les diagnostics de traduction sont désactivés par défaut ; ils sont activés
  ici via [`.symfony-lsp.json`](.symfony-lsp.json). Sans ce fichier, il ne reste
  que 6 diagnostics sur 8.
- Les options de formulaire ne sont analysées que dans une classe étendant
  `AbstractType`. Un `createFormBuilder()` construit à la volée dans un
  contrôleur n'est pas couvert.
- Un diagnostic volontaire peut être neutralisé sur place, dans un commentaire
  natif PHP, Twig, YAML ou XML :
  `{# @symfony-lsp-ignore template.not_found (raison) #}`.
- `--source-only` ne détecte **aucune** des erreurs de ce dépôt : toutes
  dépendent du conteneur compilé, donc de l'exécution de l'application.
- L'analyse par défaut **exécute le code de l'application** pour lire ses
  métadonnées. `--source-only` s'en abstient, au prix des diagnostics qui
  dépendent du conteneur compilé.
- Symfony Language Tools est en beta et évolue vite ; la version utilisée est
  épinglée dans le workflow de CI.

## Licence

MIT, voir [LICENSE](LICENSE).

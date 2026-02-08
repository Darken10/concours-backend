# Système d'Audit Complet

Un système d'audit complet qui permet de tracer qui fait quoi, quand, d'où dans votre application.

## Fonctionnalités

- ✅ Suivi automatique des créations, modifications et suppressions
- ✅ Enregistrement de l'utilisateur, l'adresse IP et le user agent
- ✅ Comparaison avant/après pour les modifications
- ✅ API RESTful pour consulter les audits
- ✅ Traçabilité complète et historique immutable

## Installation

Le système d'audit est déjà installé. Voici comment l'utiliser :

### 1. Ajouter le trait Auditable à un modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Post extends Model
{
    use Auditable;
    
    // ...reste du modèle
}
```

### 2. Points d'accès de l'API

L'API expose plusieurs endpoints pour tracer les actions :

#### Récupérer tous les audits
```
GET /api/audits
```

Paramètres de pagination et filtrage :
- `per_page` : Nombre d'audits par page (défaut: 20, max: 100)
- `action` : Filtrer par action (created, updated, deleted, restored, force_deleted)
- `model_type` : Filtrer par type de modèle (ex: App\Models\Post)

Exemple :
```bash
GET /api/audits?per_page=50&action=updated&model_type=App%5CModels%5CPost
```

#### Voir les détails d'un audit
```
GET /api/audits/{id}
```

#### Récupérer les audits d'un utilisateur
```
GET /api/audits/user/{userId}?per_page=20
```

#### Récupérer les audits d'un modèle spécifique
```
GET /api/audits/model/{modelType}/{modelId}?per_page=20
```

Exemple pour un Post avec ID "123abc":
```bash
GET "/api/audits/model/App%5CModels%5CPost/123abc"
```

#### Voir les statistiques d'audit
```
GET /api/audits/stats
```

Retourne :
```json
{
  "total_audits": 1250,
  "audits_this_month": 145,
  "audits_today": 23,
  "by_action": {
    "created": 450,
    "updated": 650,
    "deleted": 150
  },
  "by_model": {
    "App\\Models\\Post": 800,
    "App\\Models\\User": 300,
    "App\\Models\\Comment": 150
  }
}
```

## Structure des données d'audit

Chaque audit contient :

```json
{
  "id": 1,
  "user_id": "uuid-of-user",
  "action": "updated",
  "model_type": "App\\Models\\Post",
  "model_id": "post-uuid",
  "original_values": {
    "title": "Old Title",
    "content": "Old content"
  },
  "new_values": {
    "title": "New Title",
    "content": "New content"
  },
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "created_at": "2026-02-08T12:00:00.000Z",
  "updated_at": "2026-02-08T12:00:00.000Z"
}
```

## Actions enregistrées

- **created** : Quand un enregistrement est créé
- **updated** : Quand un enregistrement est modifié
- **deleted** : Quand un enregistrement est supprimé (soft delete)
- **restored** : Quand un enregistrement supprimé est restauré (soft delete)
- **force_deleted** : Quand un enregistrement est supprimé de façon permanente

## Exemple d'utilisation en code

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\AuditService;

class PostController extends Controller
{
    public function __construct(private AuditService $auditService)
    {}

    public function show(Post $post)
    {
        // Récupérer l'historique du post
        $audits = $this->auditService->getAuditsFor($post);
        
        return response()->json([
            'post' => $post,
            'audit_trail' => $audits,
        ]);
    }

    public function store(Request $request)
    {
        $post = Post::create($request->validated());
        
        // L'audit est créé automatiquement via le trait Auditable
        
        return response()->json($post, 201);
    }

    public function update(Request $request, Post $post)
    {
        $post->update($request->validated());
        
        // L'audit est créé automatiquement avec avant/après
        
        return response()->json($post);
    }
}
```

## Sécurité et Autorisations

L'accès aux audits est contrôlé par une policy :

- Seuls les **Super Admins** et **Admins** peuvent voir tous les audits
- Les utilisateurs peuvent voir leurs propres audits
- Les routes d'audit nécessitent une authentification (`auth:sanctum`)

## Base de données

Table `audits` :

```
id                 (BigInt, Primary Key)
user_id            (UUID, Foreign Key → users.id)
action             (String)
model_type         (String)
model_id           (String)
original_values    (JSON)
new_values         (JSON)
ip_address         (String)
user_agent         (String)
created_at         (Timestamp)
updated_at         (Timestamp)
```

Indexes :
- `model_type + model_id` : Pour requêtes rapides par modèle
- `user_id + created_at` : Pour requêtes rapides par utilisateur
- `action` : Pour filtrer par type d'action

## Migration

La migration a été créée automatiquement :

```
database/migrations/2026_02_08_113629_create_audits_table.php
```

## Logs des Modifications

Pour chaque modification, le système enregistre :

- **Qui** : ID de l'utilisateur authentifié
- **Quoi** : Les champs modifiés avec valeurs avant/après
- **Quand** : Timestamp exact de l'action
- **D'où** : Adresse IP du client
- **Pour Web** : User Agent du navigateur

## Conseils de Performance

1. **Housekeeping régulier** : Supprimez les vieux audits pour maintenir les performances
   
   ```php
   // Supprimer les audits de plus de 1 an
   Audit::where('created_at', '<', now()->subYear())->delete();
   ```

2. **Indexes** : Les indexes sont déjà en place pour les requêtes courantes

3. **Pagination** : Utilisez toujours la pagination pour les résultats

## Modèles déjà auditables

Ajoutez le trait `Auditable` à n'importe quel modèle pour le rendre traçable.

Exemple :
```php
use App\Traits\Auditable;

class User extends Model
{
    use Auditable;
}
```

## Diagnostic

Pour tester le système :

```bash
# Voir les audits récents
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/audits?per_page=10

# Voir les statistiques
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/audits/stats
```

## Notes d'implémentation

- Le système utilise les **Model Events** de Laravel (creating, created, updating, updated, deleting, forceDeleted, restored)
- Les valeurs originales et modifiées sont stockées en JSON pour flexibilité
- L'authentification par IP (user_agent) aide à identifier les accès suspects
- Le système fonctionne aussi avec les soft deletes

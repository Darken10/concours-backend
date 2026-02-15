# Flux d'Authentification avec Vérification OTP

## Vue d'ensemble

Le système d'authentification implémente un flux où l'utilisateur est **automatiquement connecté** après l'inscription, mais avec un **accès limité** jusqu'à la vérification de son email via un code OTP à 6 chiffres.

## Flux Détaillé

### 1. Inscription (Registration)

**Étapes :**
1. L'utilisateur remplit le formulaire d'inscription (nom, prénom, email, mot de passe, etc.)
2. Il clique sur le bouton "S'inscrire"
3. Le backend crée le compte avec :
   - Statut : `ACTIVE`
   - `email_verified_at` : `null` (email non vérifié)
   - Génération d'un code OTP à 6 chiffres
   - Expiration du code : 15 minutes
4. Un email contenant le code OTP est envoyé immédiatement
5. **Le backend retourne un token d'authentification** même si l'email n'est pas vérifié
6. **L'utilisateur est automatiquement connecté** (token stocké dans SecureStore/localStorage)
7. Redirection vers la page de vérification OTP avec l'email en paramètre

**Fichiers concernés :**
- API : `app/Services/AuthService.php` → `registerUser()` et `registerUserWithOrganization()`
- Mobile : `src/services/auth.service.ts` → `register()`
- Mobile : `src/context/AuthContext.tsx` → `register()`
- Mobile : `app/(auth)/register.tsx`

### 2. Limitation d'Accès

**Comportement :**
- L'utilisateur est **authentifié** (possède un token valide)
- Mais il **ne peut accéder qu'à la page de vérification OTP**
- Toute tentative d'accès aux pages protégées le redirige automatiquement vers `/verify-email`

**Implémentation :**
- Une vérification dans `(protected)/_layout.tsx` détecte si `user.email_verified_at` est `null`
- Si oui, redirection automatique vers `/(auth)/verify-email?email={user.email}`

**Fichiers concernés :**
- Mobile : `app/(protected)/_layout.tsx`

### 3. Vérification OTP

**Page de Vérification :**
- Interface avec 6 champs pour saisir le code à 6 chiffres
- Auto-focus sur le champ suivant lors de la saisie
- Soumission automatique lorsque les 6 chiffres sont entrés
- Timer de 60 secondes avant de pouvoir renvoyer un nouveau code
- Récupère l'email depuis :
  - Les paramètres de l'URL (venant de l'inscription)
  - OU depuis `user.email` (utilisateur déjà connecté redirigé depuis protected)

**Validation du Code :**
1. L'utilisateur saisit le code OTP
2. Envoi au backend via `POST /api/auth/email/verify`
3. Le backend vérifie :
   - Code correct ?
   - Code non expiré ?
   - Email pas déjà vérifié ?
4. Si valide :
   - `email_verified_at` = maintenant
   - Code OTP supprimé de la base
   - Retour des données utilisateur mises à jour
5. Le frontend met à jour l'utilisateur dans le contexte
6. **Redirection automatique vers la page d'accueil** `/(protected)/blog`
7. L'utilisateur a maintenant **accès complet** à toutes les fonctionnalités

**Fichiers concernés :**
- API : `app/Http/Controllers/Api/EmailVerificationController.php` → `verify()`
- Mobile : `src/services/auth.service.ts` → `verifyEmail()`
- Mobile : `src/context/AuthContext.tsx` → `verifyEmail()`
- Mobile : `app/(auth)/verify-email.tsx`

### 4. Renvoyer le Code

**Fonctionnalité :**
- Si le code n'est pas reçu ou a expiré, l'utilisateur peut demander un nouveau code
- Disponible après 60 secondes (timer)
- Génère un nouveau code à 6 chiffres avec nouvelle expiration de 15 minutes
- Envoie un nouvel email

**Endpoint :**
- `POST /api/auth/email/resend`

**Fichiers concernés :**
- API : `app/Http/Controllers/Api/EmailVerificationController.php` → `resend()`
- Mobile : `src/services/auth.service.ts` → `resendVerificationCode()`
- Mobile : `app/(auth)/verify-email.tsx` → `handleResend()`

### 5. Protection de la Connexion (Login)

**Comportement :**
- Les utilisateurs qui ont un compte mais **n'ont pas encore vérifié leur email** ne peuvent **PAS se connecter** via le formulaire de login classique
- Erreur affichée : "Veuillez vérifier votre adresse email avant de vous connecter"
- Ils doivent d'abord valider leur email via le code OTP reçu

**Implémentation :**
- Vérification dans `AuthService::loginUser()` :
  ```php
  if ($user->email_verified_at === null) {
      throw ValidationException::withMessages([
          'email' => ['Veuillez vérifier votre adresse email avant de vous connecter'],
      ]);
  }
  ```

**Fichiers concernés :**
- API : `app/Services/AuthService.php` → `loginUser()`

## Schéma du Flux

```
┌─────────────────┐
│   Inscription   │
│  (Formulaire)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Compte créé    │
│  + Token généré │
│  + OTP envoyé   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌──────────────────────┐
│  Connexion AUTO │────▶│  Vérification Email  │
│  (avec token)   │     │  (Accès LIMITÉ)      │
└─────────────────┘     └──────────┬───────────┘
                                   │
                    ┌──────────────┴──────────────┐
                    │                             │
                    ▼                             ▼
          ┌──────────────────┐          ┌─────────────────┐
          │  Tentative accès │          │  Saisie code    │
          │  pages protégées │          │  OTP (6 chiff.) │
          └────────┬─────────┘          └────────┬────────┘
                   │                             │
                   │                             ▼
                   │                    ┌─────────────────┐
                   │                    │  Vérification   │
                   │                    │  réussie        │
                   │                    └────────┬────────┘
                   │                             │
                   │                             ▼
                   │                    ┌─────────────────┐
                   │                    │  email_verified │
                   │                    │  _at mis à jour │
                   │                    └────────┬────────┘
                   │                             │
                   └─────────────────────────────┤
                                                 │
                                                 ▼
                                        ┌─────────────────┐
                                        │  Accès COMPLET  │
                                        │  à la plateforme│
                                        └─────────────────┘
```

## Points Clés

### ✅ Avantages de ce Flux

1. **Meilleure UX** : Pas besoin de se reconnecter après vérification
2. **Simplicité** : Un seul parcours fluide du début à la fin
3. **Sécurité maintenue** : Accès limité tant que l'email n'est pas vérifié
4. **Session immédiate** : L'utilisateur n'a pas l'impression de perdre sa progression

### 🔒 Sécurité

- Token généré dès l'inscription mais accès limité
- Impossible de se connecter via login si email non vérifié
- Code OTP expire après 15 minutes
- Seule la page de vérification est accessible avant validation
- Protection côté backend ET frontend

### 📱 Gestion Mobile

- Token stocké dans SecureStore (iOS/Android) ou localStorage (Web)
- Utilisateur persisté dans AsyncStorage pour réhydratation
- Navigation automatique basée sur le statut de vérification
- Gestion des erreurs avec messages clairs

## Fichiers Modifiés

### Backend (API)
- `app/Services/AuthService.php`
- `app/Http/Controllers/Api/EmailVerificationController.php`
- `routes/api/auth.php`
- `database/migrations/*_add_email_verification_code_to_users_table.php`
- `app/Notifications/EmailVerificationCode.php`

### Frontend (Mobile)
- `src/services/auth.service.ts`
- `src/context/AuthContext.tsx`
- `src/types/auth.ts`
- `app/(auth)/_layout.tsx`
- `app/(auth)/register.tsx`
- `app/(auth)/verify-email.tsx` (nouveau)
- `app/(protected)/_layout.tsx`

## Tests

### Tests API
- **14 tests** pour la vérification email
- **47 assertions** au total
- ✅ Tous les tests passent

Exécuter les tests :
```bash
php artisan test --filter=EmailVerificationTest
```

## Configuration

### Variables d'Environnement
Aucune variable d'environnement supplémentaire requise. Le système utilise la configuration email existante de Laravel.

### Base de Données
Colonnes ajoutées à la table `users` :
- `email_verification_code` (string, 6, nullable)
- `email_verification_code_expires_at` (timestamp, nullable)

## Prochaines Étapes Possibles

1. **Analytics** : Tracker le taux de vérification email
2. **Notifications Push** : Envoyer aussi le code par notification mobile
3. **Personnalisation** : Template email plus riche
4. **Limites de tentatives** : Bloquer après X échecs de code
5. **Support multicanal** : Vérification par SMS en alternative

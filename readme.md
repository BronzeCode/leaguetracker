
# League Tracker

Este proyecto es un sistema de seguimiento de rangos de jugadores, configurado para funcionar con **DDEV** y **Docker**, incluyendo un entorno de base de datos MySQL y PhpMyAdmin.

---

## 🚀 Requisitos previos

Antes de iniciar el proyecto, asegúrate de tener instalado:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [DDEV](https://ddev.readthedocs.io/en/stable/)
- [Git](https://git-scm.com/)

---

## ⚙️ Instalación y configuración

### 1. Clonar el repositorio

```bash
git clone git@github.com:BronzeCode/leaguetracker.git
cd leaguetracker
```

### 2. Iniciar el entorno DDEV

```bash
ddev start
```

Esto levantará automáticamente:
- Un contenedor PHP (según la configuración de `.ddev/config.yaml`)
- Una base de datos MySQL
- PhpMyAdmin en `http://leaguetracker.ddev.site:8036`

### 3. Importar la base de datos (si existe)

Si tienes un archivo de base de datos (por ejemplo `db.sql`), puedes cargarlo con:

```bash
ddev import-db --src=db.sql
```

### 4. Cargar datos de ejemplo (si no hay base de datos)

Si el proyecto no tiene archivo `.env`, automaticamente se usa el `data.json` para datos iniciales de prueba.
Ejemplo de `data.json`:

```json
{
  "rank_history": [
    {
      "fecha": "2025-10-30",
      "jugador": "Jugador1",
      "tier": "IRON",
      "division": "III",
      "lp": 65,
      "wins": 7,
      "losses": 17
    },
    {
      "fecha": "2025-10-31",
      "jugador": "Jugador2",
      "tier": "IRON",
      "division": "IV",
      "lp": 94,
      "wins": 52,
      "losses": 103
    },
    {
      "fecha": "2025-11-05",
      "jugador": "Jugador3",
      "tier": "SILVER",
      "division": "IV",
      "lp": 72,
      "wins": 102,
      "losses": 110
    }
  ]
}
```

---

## 🧰 Comandos útiles

| Acción | Comando |
|--------|----------|
| Iniciar entorno | `ddev start` |
| Detener entorno | `ddev stop` |
| Ver logs | `ddev logs` |
| Acceder al contenedor web | `ddev ssh` |
| Importar base de datos | `ddev import-db --src=db.sql` |
| Abrir PhpMyAdmin | [http://leaguetracker.ddev.site:8036](http://leaguetracker.ddev.site:8036) |

---

## 🧩 Estructura del proyecto

```
LeagueTracker
├── .env.example # Credenciales Base de datos y Api Key Riot
├── .gitignore
├── db
│   ├── schema.sql # Estructura de la base de datos
│   └── seed.sql # Datos dummy base de datos
├── load_env.php # Funciona para cargar los datos de Base y Api desde .env
├── public # Archivos que estaran accesibles desde la web.
│   ├── data.json # Datos dummy por si no se encuentra el .env (ideal si no se quiere trabajar con base de datos solo con frontend)
│   ├── index.php # Frontend principal para trabajar solo con el frontend
├── readme.md 
└── update_all.php # Script para consumir desde API Riot y guardar en base de datos solo funciona si esta configurada una base de datos.
```

---

## 🧠 Contribución

1. Crea un **issue** en GitHub con la descripción del cambio.
2. Crea un **branch** a partir del número del issue, por ejemplo:

   ```bash
   git checkout -b features/3-generar-readme-instalacion
   ```

3. Realiza tus cambios y haz commit:

   ```bash
   git add .
   git commit -m "Generar README de instalación"
   ```

4. Sube tu branch y crea un **Pull Request (MR)**:

   ```bash
   git push origin features/3-generar-readme-instalacion
   ```

5. Espera revisión y aprobación antes de hacer merge.

---


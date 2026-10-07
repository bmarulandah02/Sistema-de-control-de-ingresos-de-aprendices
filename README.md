# Sistema de Control de Ingreso y Asistencia de Aprendices — SENA

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-HTTP_Server-D22128?style=for-the-badge&logo=apache&logoColor=white)
![Vanilla CSS](https://img.shields.io/badge/CSS-Shadcn_UI_Aesthetics-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![SENA](https://img.shields.io/badge/Institución-SENA-39A900?style=for-the-badge)

Sistema web integral desarrollado bajo la arquitectura **MVC (Modelo - Vista - Controlador)** en PHP y MySQL para la gestión, control biométrico/RFID de ingresos, administración de horarios formativos por bloques, seguimiento de excusas médicas y control preventivo de deserción escolar de aprendices en el Servicio Nacional de Aprendizaje (SENA).

---

## Tabla de Contenido
1. [Características Principales](#-características-principales)
2. [Estructura del Proyecto](#-estructura-del-proyecto)
3. [Módulos del Sistema](#-módulos-del-sistema)
4. [Roles y Permisos](#-roles-y-permisos)
5. [Requisitos del Sistema](#-requisitos-del-sistema)
6. [Instalación y Puesta en Marcha](#-instalación-y-puesta-en-marcha)
7. [Configuración del Servidor y Seguridad](#-configuración-del-servidor-y-seguridad)
8. [Marco Normativo Aplicado](#-marco-normativo-aplicado)

---

## 🚀 Características Principales

- **Terminal de Registro de Asistencia RFID / Carné**: Lectura rápida de tarjetas RFID para registrar ingresos y salidas, con validación estricta de pertenencia de la ficha y cálculo automático de retardos.
- **Gestión Avanzada de Horarios por Bloques**: Escaneo e importación masiva de cronogramas y mallas horarias oficiales desde plantillas Excel. Visualización mensual interactiva tipo calendario e impresión/exportación de horarios en PDF y Excel.
- **Gestión Integral de Excusas Médicas**: Portal para que los aprendices anexen incapacidades médicas (EPS/IPS) o de fuerza mayor, con bandeja de aprobación y rechazo para instructores.
- **Módulo de Alertas de Deserción y Requerimientos Legales**: Identificación automática de aprendices con inasistencias injustificadas, clasificación por semáforo de riesgo (Causal de Deserción, Riesgo Alto, Alerta) y emisión de comunicaciones oficiales de descargos con plazo de 5 días hábiles (Acuerdo 007 de 2012, Art. 22).
- **Reportes y Analíticas**: Generación de reportes ejecutivos en formatos PDF y Excel con filtros por fecha, estado de ingreso, ficha y docente.
- **Diseño Moderno y Responsivo**: Interfaz construida con principios de diseño Shadcn UI, tipografía Plus Jakarta Sans / Inter, soporte de tema Claro/Oscuro y compatibilidad con dispositivos móviles.

---

## 📂 Estructura del Proyecto

```text
Sistema-de-control-de-ingresos-de-aprendices/
│
├── config/                               # Configuraciones globales y seguridad
│   ├── .htaccess                         # Protección de acceso HTTP al directorio
│   ├── database.php                      # Parámetros y credenciales de conexión PDO
│   └── apache/
│       └── .htaccess                     # Directivas del servidor Apache (403, 404, Options -Indexes)
│
├── core/                                 # Núcleo de la aplicación
│   └── Router.php                        # Enrutador central y control de acceso (RBAC)
│
├── controllers/                          # Controladores (Lógica de la aplicación)
│   ├── AsistenciaController.php          # Terminal RFID, apertura/cierre de sesión y marcado
│   ├── AuthController.php                # Inicio y cierre de sesión, control de autenticación
│   ├── DesercionController.php           # Alertas de inasistencias, trámite y citaciones oficiales
│   ├── ExcusaController.php              # Radicación, validación, aprobación y rechazo de excusas
│   ├── FichaController.php               # CRUD de fichas y gestión de horarios por bloques
│   ├── ReporteController.php             # Exportación e impresión (PDF/Excel)
│   └── UsuarioController.php             # CRUD de usuarios, aprendices y perfil personal
│
├── models/                               # Modelos (Acceso a base de datos PDO)
│   ├── AprendizModel.php                 # Consultas de aprendices y fichas asignadas
│   ├── DesercionModel.php                # Algoritmo de inasistencias continuas y causales legales
│   ├── ExcusaModel.php                   # Persistencia y estados de soporte médico
│   ├── HorarioModel.php                  # Importador Excel de horarios y bloques de formación
│   ├── IngresoModel.php                  # Transacciones de asistencia e historial de accesos
│   ├── ReporteModel.php                  # Métricas consolidadas y agregaciones para reportes
│   └── UsuarioModel.php                  # Autenticación, hashes y roles de usuario
│
├── database/                             # Clases auxiliares de base de datos
│   └── Database.php                      # Envoltorio de conexión MySQL PDO
│
├── views/                                # Vistas (Plantillas HTML / PHP)
│   ├── admin/                            # Vistas del panel de administración
│   │   ├── dashboard.php                 # Panel principal con KPIs en tiempo real
│   │   ├── fichas.php                    # Lista de fichas formativas
│   │   ├── formulario_ficha.php          # Creación y edición de fichas
│   │   ├── formulario_usuario.php        # Registro y edición de instructores/aprendices
│   │   ├── reportes.php                  # Centro de descargas y filtros de reportes
│   │   ├── reporte_pdf.php               # Plantilla imprimible de reportes
│   │   └── usuarios.php                  # Gestión de cuentas y credenciales
│   ├── aprendiz/                         # Vistas del portal del aprendiz
│   │   ├── excusas.php                   # Bandeja de excusas radicadas
│   │   ├── formulario_excusa.php         # Formulario de subida de incapacidad
│   │   ├── perfil.php                    # Perfil, carné digital e historial propio
│   │   └── reporte_faltas_pdf.php        # Reporte descargable de faltas individuales
│   ├── asistencia/                       # Vistas de captura de ingresos
│   │   ├── historial.php                 # Consulta en vivo de asistencias registradas
│   │   └── registro.php                  # Terminal interactiva de escaneo RFID
│   ├── auth/                             # Vistas de autenticación
│   │   └── login.php                     # Pantalla de acceso al sistema
│   ├── desercion/                        # Módulo de deserción escolar
│   │   ├── citacion.php                  # Oficio oficial SENA de requerimiento (imprimible)
│   │   └── index.php                     # Tablero de control de alumnos en riesgo de deserción
│   ├── errors/                           # Páginas de error personalizadas
│   │   ├── 403.php                       # Acceso denegado / Prohibido
│   │   └── 404.php                       # Página o recurso no encontrado
│   ├── fichas/                           # Gestión detallada de horarios
│   │   ├── formulario.php                # Ajustes de la ficha
│   │   ├── horario.php                   # Vista de calendario mensual de clases
│   │   ├── horario_imprimir.php          # Formato oficial de horario para imprimir / PDF
│   │   └── importar_horario.php          # Escáner y subida de archivos Excel de horario
│   └── layouts/                          # Componentes reutilizables
│       ├── footer.php                    # Pie de página y scripts globales
│       └── header.php                    # Barra superior, navegación y tema
│
├── public/                               # Archivos estáticos y de descarga pública
│   ├── css/                              # Hojas de estilo personalizadas
│   │   ├── reporte_pdf.css               # Estilos para generación de documentos PDF
│   │   ├── style403.css                  # Estilo temático error 403
│   │   ├── style404.css                  # Estilo temático error 404
│   │   └── styles.css                    # Estilos principales del sistema
│   ├── js/                               # Scripts interactivos
│   │   └── main.js                       # Lógica de interfaz, modales y tema
│   └── uploads/                          # Almacén de archivos subidos
│       └── excusas/                      # Documentos de soporte médico (PDF, imágenes)
│
├── asistencia_aprendices.sql             # Script SQL de creación e inicialización de la BD
├── asistencias_aprendices.mwb            # Modelo relacional en MySQL Workbench
├── index.php                             # Front Controller (Punto de entrada único)
└── README.md                             # Documentación del proyecto
```

---

## 📌 Módulos del Sistema

### 1. Terminal de Asistencia RFID
- Permite la selección de la ficha y materia correspondiente a la jornada.
- Valida en tiempo real que el aprendiz que presenta el carné pertenezca a la ficha formativa seleccionada.
- Clasifica la marcación como **Presente** o **Retardo** según la franja horaria programada.

### 2. Horarios Mensuales y Escáner Excel
- Importa archivos `.xlsx` y `.xls` respetando el formato de programación de ambientes SENA.
- Distribuye la formación en bloques horarios interactivos por mes.
- Exporta de regreso los horarios en formato limpio de Excel o en vistas imprimibles de alta fidelidad.

### 3. Excusas Médicas
- El aprendiz carga el certificado médico expedido por la EPS o constancia formal.
- El instructor revisa el documento adjunto y define el estado: *Pendiente*, *Aprobada* o *Rechazada*, actualizando el estado de la falta en el historial.

### 4. Alertas de Deserción y Citación Oficial
- Algoritmo que monitorea inasistencias acumuladas y consecutivas sin soporte médico.
- **Categorías de riesgo**:
  - 🔴 **Causal de Deserción**: 3 o más inasistencias continuas sin justificar.
  - 🟠 **Riesgo Alto**: 2 inasistencias continuas o 3+ acumuladas.
  - 🟡 **Alerta**: Inasistencia reciente sin justificar.
- Genera el **Oficio de Requerimiento Formal y Citación a Descargos**, otorgando los **5 días hábiles** estipulados por la ley antes de la cancelación de matrícula.
- Botones de acción directa para contactar al aprendiz vía WhatsApp o Correo electrónico institucional.

---

## 👥 Roles y Permisos

| Módulo / Funcionalidad | Administrador | Instructor | Aprendiz |
|---|:---:|:---:|:---:|
| Panel de Control (Dashboard) | ✅ Completo | ✅ Fichas asignadas | ❌ |
| Terminal RFID de Asistencia | ✅ | ✅ | ❌ |
| Gestión de Usuarios | ✅ | ❌ | ❌ |
| Gestión de Fichas (Crear/Editar) | ✅ | ❌ (Solo lectura) | ❌ |
| Importación y Visualización de Horarios | ✅ | ✅ | ✅ (Solo vista) |
| Aprobación de Excusas Médicas | ✅ | ✅ | ❌ |
| Radicación de Excusas Médicas | ❌ | ❌ | ✅ |
| Alertas de Deserción y Citaciones | ✅ | ✅ | ❌ |
| Reportes Generales PDF / Excel | ✅ | ✅ | ❌ |
| Consulta de Asistencias Propias | ❌ | ❌ | ✅ |

---

## 💻 Requisitos del Sistema

- **Servidor Web**: Apache 2.4 o superior (con soporte para `.htaccess` y `mod_rewrite` opcional).
- **PHP**: Versión 8.1, 8.2 o superior.
  - Extensiones requeridas: `pdo_mysql`, `mbstring`, `gd`, `zip`, `fileinfo`.
- **Base de Datos**: MySQL 8.0+ o MariaDB 10.4+.
- **Entorno recomendado**: [Laragon](https://laragon.org/) (Full) o [XAMPP](https://www.apachefriends.org/).

---

## 🛠 Instalación y Puesta en Marcha

### 1. Clonar el Repositorio
Ubica el repositorio en la carpeta raíz de tu servidor web (en Laragon suele ser `C:\laragon\www\`):
```bash
cd C:\laragon\www\Sistema-Ingreso
git clone https://github.com/bmarulandah02/Sistema-de-control-de-ingresos-de-aprendices
```

### 2. Configurar la Base de Datos
1. Abre tu gestor de base de datos favorito (HeidiSQL, phpMyAdmin, MySQL Workbench o DBeaver).
2. Crea una base de datos denominada `control_ingreso_aprendices` (o el nombre de tu preferencia con cotejamiento `utf8mb4_general_ci`).
3. Importa el archivo SQL incluido en la raíz:
   ```sql
   source asistencia_aprendices.sql;
   ```

### 3. Ajustar Parámetros de Conexión
Verifica o edita las credenciales en [`config/database.php`](file:///c:/laragon/www/Sistema-Ingreso/Sistema-de-control-de-ingresos-de-aprendices/config/database.php):
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'control_ingreso_aprendices');
define('DB_CHARSET', 'utf8mb4');
```

### 4. Ejecutar el Proyecto
Inicia los servicios de Apache y MySQL en Laragon y accede desde el navegador:
```text
http://localhost/Sistema-Ingreso/Sistema-de-control-de-ingresos-de-aprendices/index.php
```

---

## 🔒 Configuración del Servidor y Seguridad

- **Protección de Directorios Internos**:
  El directorio [`config/`](file:///c:/laragon/www/Sistema-Ingreso/Sistema-de-control-de-ingresos-de-aprendices/config) cuenta con su propio archivo `.htaccess` que bloquea el acceso HTTP directo a archivos confidenciales como credenciales de base de datos.
- **Configuración Apache Centralizada**:
  Las directivas de servidor (manejadores de error 403 y 404, restricción de listado de directorios `Options -Indexes`) se encuentran organizadas en [`config/apache/.htaccess`](file:///c:/laragon/www/Sistema-Ingreso/Sistema-de-control-de-ingresos-de-aprendices/config/apache/.htaccess).
- **Protección contra Inyecciones SQL**:
  Todas las transacciones con la base de datos se ejecutan a través de **PDO con sentencias preparadas y parámetros vinculados (bound parameters)**.
- **Saneamiento XSS**:
  Toda salida enviada a las vistas se procesa mediante `htmlspecialchars()` con codificación UTF-8.

---

## ⚖️ Marco Normativo Aplicado

El módulo de **Deserción e Inasistencias** se fundamenta en el marco legal del SENA:
- **Acuerdo 007 de 2012** (*Reglamento del Aprendiz SENA*):
  - **Capítulo VII, Artículo 22 (Causales de Deserción)**: Se configura causal de deserción cuando el aprendiz acumule tres (3) días consecutivos de inasistencia no justificada a las actividades de formación.
  - **Debido Proceso**: El Centro de Formación emite un requerimiento oficial otorgando un término improrrogable de **cinco (5) días hábiles** para aportar los descargos y soportes pertinentes.

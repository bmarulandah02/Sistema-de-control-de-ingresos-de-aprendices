# Estructura de Carpetas Propuesta
´´´
sistema-control-ingreso/
│
├── config/
│   └── database.php          # Conexión a la base de datos MySQL
│
├── core/
│   └── Router.php            # Manejo de rutas del sistema
│
├── models/                   # Modelos: Interacción con la base de datos
│   ├── UsuarioModel.php      # Autenticación y datos generales de usuarios
│   ├── AprendizModel.php     # Gestión de aprendices inscritos y perfiles
│   ├── HorarioModel.php      # Gestión de horarios de fichas y cursos
│   ├── IngresoModel.php      # Control de RFID, entradas, salidas y mantenimiento
│   └── ExcusaModel.php       # Gestión y estados de excusas médicas
│
├── controllers/              # Controladores: Lógica de negocio y flujo
│   ├── AuthController.php    # Control de inicio y cierre de sesión
│   ├── AsistenciaController.php # Registro RFID, cálculo de retardos y limpieza
│   ├── ReporteController.php # Generación de reportes (PDF/Excel) y consultas
│   ├── ExcusaController.php   # Aprobación, rechazo y carga de excusas
│   └── FichaController.php    # Gestión de cursos y horarios por el instructor
│
├── views/                    # Vistas: Interfaces que ve el usuario
│   ├── layouts/
│   │   ├── header.php        # Encabezado común y navegación
│   │   └── footer.php        # Pie de página común
│   ├── auth/
│   │   └── login.php         # Formulario de inicio de sesión
│   ├── admin/
│   │   ├── dashboard.php     # Panel de control del Administrador/Instructor
│   │   ├── reportes.php      # Panel para generar reportes en PDF/Excel
│   │   └── fichas.php        # Gestión de horarios y cursos
│   └── aprendiz/
│       ├── perfil.php        # Historial de asistencias e inasistencias
│       └── excusas.php       # Formulario para anexar excusas médicas
│
├── public/                   # Archivos accesibles públicamente
│   ├── css/
│   │   └── styles.css        # Estilos para la interfaz adaptable
│   ├── js/
│   │   └── main.js           # Scripts del lado del cliente
│   ├── uploads/
│   │   └── excusas/          # Almacenamiento de archivos de excusas médicas
│   └── index.php             # Punto de entrada único de la aplicación
│
└── vendor/                   # Librerías externas (Ej. FPDF/PhpSpreadsheet)
´´´

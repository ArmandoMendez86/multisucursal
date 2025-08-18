<?php
// Se define la página actual al inicio del propio menú de navegación
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
  /* --- ESTILOS ORIGINALES --- */
  .tooltip-container {
    position: relative;
  }

  .tooltip-container .tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 12px;
    padding: 6px 12px;
    background-color: #1e2b3b;
    color: #fff;
    border: 1px solid #4a5568;
    border-radius: 6px;
    font-size: 0.875rem;
    white-space: nowrap;
    z-index: 10;
    transition: opacity 0.2s ease-in-out;
    pointer-events: none;
  }

  #sidebar.w-24 .tooltip-container:hover .tooltip {
    visibility: visible;
    opacity: 1;
  }

  #sidebar.w-24 nav {
    overflow: visible;
  }

  #sidebar.w-24 nav a {
    justify-content: center;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }

  #sidebar.w-24 #logo-container {
    width: 2.5rem;
    height: 2.5rem;
    margin-bottom: 0;
  }

  #sidebar.w-24 .sidebar-header {
    height: 6rem;
  }
  
  #sidebar-overlay {
    transition: opacity 0.3s ease-in-out;
  }
</style>

<!-- MODIFICADO: Superposición ahora se oculta en el breakpoint 'lg' -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<!-- MODIFICADO: Clases de la barra lateral ahora usan el breakpoint 'lg' -->
<aside id="sidebar"
  class="bg-[#1e293b] text-gray-300 flex flex-col h-screen w-64 fixed inset-y-0 left-0 z-40 lg:relative lg:translate-x-0 transform -translate-x-full transition-transform duration-300 ease-in-out">
  
  <!-- Logo y Nombre de Sucursal -->
  <div
    class="sidebar-header p-6 text-center h-48 flex-shrink-0 flex flex-col justify-center transition-all duration-300 ease-in-out">
    <div id="logo-container"
      class="mx-auto mb-4 w-20 h-20 flex items-center justify-center bg-gray-700 rounded-full transition-all duration-300 ease-in-out">
      <img src="img/logo.png" alt="Logo" class="rounded-full">
    </div>
    <h2 id="sucursal-nombre" class="text-xl font-semibold text-white nav-text">
      <?php echo isset($_SESSION['branch_name']) ? htmlspecialchars($_SESSION['branch_name']) : 'Mi Sucursal'; ?>
    </h2>
  </div>

  <!-- Menú de Navegación -->
  <nav class="flex-1 px-4 py-2 space-y-2 overflow-y-auto min-h-0 overflow-x-hidden">

    <a href="pos.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'pos.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
      <i class="fas fa-cash-register fa-fw w-6 h-6"></i>
      <span class="nav-text ml-3">Ventas</span>
      <span class="tooltip">Ventas</span>
    </a>
    <a href="gastos.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'gastos.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
      <i class="fas fa-file-invoice-dollar fa-fw w-6 h-6"></i>
      <span class="nav-text ml-3">Gastos</span>
      <span class="tooltip">Gastos</span>
    </a>
    <a href="reportes.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'reportes.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
      <i class="fas fa-chart-line fa-fw w-6 h-6"></i>
      <span class="nav-text ml-3">Reportes</span>
      <span class="tooltip">Reporte de Ventas</span>
    </a>
    
    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] == 'Administrador' || $_SESSION['rol'] == 'Super')): ?>
      <a href="dashboard.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'dashboard.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
        <i class="fas fa-tachometer-alt fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Dashboard</span>
        <span class="tooltip">Dashboard</span>
      </a>
      <a href="inventario.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'inventario.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
        <i class="fas fa-boxes-stacked fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Inventario</span>
        <span class="tooltip">Inventario</span>
      </a>
      <a href="clientes.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'clientes.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
        <i class="fas fa-users fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Clientes</span>
        <span class="tooltip">Clientes</span>
      </a>
      <a href="configuracion.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'configuracion.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
        <i class="fas fa-cog fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Configuración</span>
        <span class="tooltip">Configuración</span>
      </a>
    <?php endif; ?>
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Super'): ?>
      <a href="reporte_global.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'reporte_global.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
        <i class="fas fa-globe-americas fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Reporte Global</span>
        <span class="tooltip">Ventas de Todas las Sucursales</span>
      </a>
      <a href="admin.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'admin.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
        <i class="fas fa-shield-alt fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Gestión</span>
        <span class="tooltip">Administración</span>
      </a>
    <?php endif; ?>
    <a href="impresoras.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'impresoras.php') ? 'bg-[#4f46e5] text-white' : 'hover:bg-gray-700'; ?>">
      <i class="fas fa-print fa-fw w-6 h-6"></i>
      <span class="nav-text ml-3">Impresoras</span>
      <span class="tooltip">Impresoras</span>
    </a>
  </nav>

  <!-- Sección inferior (fija) -->
  <div class="flex-shrink-0">
    <!-- MODIFICADO: Botón para colapsar ahora se oculta en pantallas menores a 'lg' -->
    <div class="px-4 py-2 border-t border-gray-700">
      <button id="sidebar-toggle"
        class="tooltip-container hidden lg:flex items-center w-full px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-gray-700">
        <i class="fas fa-chevron-left fa-fw w-6 h-6" id="toggle-icon"></i>
        <span class="nav-text ml-3">Ocultar</span>
        <span class="tooltip">Ocultar Menú</span>
      </button>
    </div>

    <!-- Información del Usuario -->
    <div class="p-4 border-t border-gray-700">
      <p id="user-nombre" class="text-sm font-semibold text-white truncate nav-text">
        <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuario'; ?>
      </p>
      <p id="user-rol" class="text-xs text-gray-400 nav-text">
        <?php echo isset($_SESSION['rol']) ? htmlspecialchars($_SESSION['rol']) : 'Rol'; ?>
      </p>
      <button id="logout-button"
        class="tooltip-container w-full mt-4 text-left text-sm text-red-400 hover:text-red-300 flex items-center">
        <i class="fas fa-sign-out-alt fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Cerrar Sesión</span>
        <span class="tooltip">Cerrar Sesión</span>
      </button>
    </div>
  </div>
</aside>

<!-- Script para la funcionalidad del menú -->
<script>
  (function () {
    const logoutButton = document.getElementById("logout-button");
    const toggleButton = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById('sidebar');
    
    // MODIFICADO: Condición de JS ahora usa el breakpoint 'lg' (1024px)
    if (sidebar && window.innerWidth >= 1024 && localStorage.getItem('sidebarCollapsed') === 'true') {
      sidebar.classList.remove('w-64', 'transition-all', 'duration-300', 'ease-in-out');
      sidebar.classList.add('w-24');
      document.querySelectorAll('.nav-text').forEach(text => text.classList.add('hidden'));
      const toggleIcon = document.getElementById('toggle-icon');
      if (toggleIcon) {
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
      }
    }

    function logout() {
      fetch("/multi-sucursal/logout", {
        method: "POST"
      }).finally(() => {
        window.location.href = "login.php";
      });
    }

    if (logoutButton) {
      logoutButton.addEventListener("click", logout);
    }

    if (sidebar && toggleButton) {
      const toggleIcon = document.getElementById("toggle-icon");
      const navTexts = document.querySelectorAll(".nav-text");

      sidebar.classList.add('transition-all', 'duration-300', 'ease-in-out');

      const applyCollapsedState = () => {
        sidebar.classList.add("w-24");
        sidebar.classList.remove("w-64");
        navTexts.forEach((text) => text.classList.add("hidden"));
        if (toggleIcon) {
          toggleIcon.classList.remove("fa-chevron-left");
          toggleIcon.classList.add("fa-chevron-right");
        }
      };

      const applyExpandedState = () => {
        sidebar.classList.add("w-64");
        sidebar.classList.remove("w-24");
        navTexts.forEach((text) => text.classList.remove("hidden"));
        if (toggleIcon) {
          toggleIcon.classList.add("fa-chevron-left");
          toggleIcon.classList.remove("fa-chevron-right");
        }
      };

      const toggleSidebar = () => {
        const isCollapsed = sidebar.classList.contains("w-24");
        if (isCollapsed) {
          applyExpandedState();
          localStorage.setItem("sidebarCollapsed", "false");
        } else {
          applyCollapsedState();
          localStorage.setItem("sidebarCollapsed", "true");
        }
      };

      toggleButton.addEventListener("click", toggleSidebar);
    }

  })();
</script>

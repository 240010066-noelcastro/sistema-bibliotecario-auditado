import { IonApp, IonRouterOutlet, IonSplitPane, setupIonicReact } from '@ionic/react';
import { IonReactRouter } from '@ionic/react-router';
import { Redirect, Route } from 'react-router-dom';
import Menu from './components/Menu';

/* Core CSS required for Ionic components to work properly */
import '@ionic/react/css/core.css';
import '@ionic/react/css/normalize.css';
import '@ionic/react/css/structure.css';
import '@ionic/react/css/typography.css';
import '@ionic/react/css/padding.css';
import '@ionic/react/css/float-elements.css';
import '@ionic/react/css/text-alignment.css';
import '@ionic/react/css/text-transformation.css';
import '@ionic/react/css/flex-utils.css';
import '@ionic/react/css/display.css';
import '@ionic/react/css/palettes/dark.system.css';

/* Theme variables */
import './theme/variables.css';

/* Importación de Páginas */
import Login from './pages/Auth/Login'; 
import CompletarRegistro from './pages/Auth/CompletarRegistro';
import Dashboard from './pages/Dashboard/Dashboard';
import Carreras from './pages/Carreras/Carreras';
import Grupos from './pages/Grupos/Grupos'; 
import Autores from './pages/Autores/Autores';
import Editoriales from './pages/Editoriales/Editoriales'; 
import Catalogo from './pages/Catalogo/Catalogo';
import Inventario from './pages/Inventario/Inventario'; 
import Prestamos from './pages/Prestamos/Prestamos'; 
import Sanciones from './pages/Sanciones/Sanciones'; 
import Usuarios from './pages/Usuarios/Usuarios';
import TabsUsuario from './pages/PortalUsuario/TabsUsuario';
import Configuracion from './pages/Configuracion/Configuracion';
import DetalleRecurso from './pages/PortalUsuario/DetalleRecurso/DetalleRecurso';
import MisPedidos from './pages/PortalUsuario/MisPedidos/MisPedidos';
import Historial from './pages/PortalUsuario/Historial/Historial';

setupIonicReact();

const App: React.FC = () => {
  return (
    <IonApp>
      <IonReactRouter>
        <IonRouterOutlet>

          {/* =========================================================
              1. LOGIN UNIFICADO (Admins y Alumnos)
              ========================================================= */}
          <Route path="/" exact={true} render={() => {
            const usuarioStr = sessionStorage.getItem('usuario');
            const rol = sessionStorage.getItem('rol');
            
            if (!usuarioStr) return <Login />;
            if (rol === 'admin') return <Redirect to="/dashboard" />;
            return <Redirect to="/portal" />;
          }} />

          <Route path="/completar-registro" exact={true}><CompletarRegistro /></Route>

          {/* =========================================================
              3. PORTAL DE USUARIO (SOLO PARA ALUMNOS)
              ========================================================= */}
          <Route path="/portal" render={() => {
            const usuarioStr = sessionStorage.getItem('usuario');
            const rol = sessionStorage.getItem('rol');

            if (!usuarioStr) return <Redirect to="/" />;
            if (rol === 'admin') return <Redirect to="/dashboard" />; 

            return <TabsUsuario />;
          }} />

          {/* RUTA DE DETALLE INDEPENDIENTE PARA ALUMNOS */}
          <Route path="/portal/recurso/:id" component={DetalleRecurso} exact={true} />

          {/* 🏛️ Ruta limpia y exclusiva para pedidos activos en posesión */}
          <Route path="/portal/pedidos" component={MisPedidos} exact={true} />

          {/* 🏛️ NUEVO: Ruta registrada independiente para la bitácora del pasado */}
          <Route path="/portal/historial" component={Historial} exact={true} />

          {/* =========================================================
              4. PANEL DE ADMINISTRADOR (SOLO PARA ADMINS)
              ========================================================= */}
          <Route path={[
            "/dashboard", "/carreras", "/grupos", "/autores", 
            "/editoriales", "/catalogo", "/inventario", 
            "/prestamos", "/sanciones", "/usuarios", "/configuracion"
          ]} render={() => {
            const usuarioStr = sessionStorage.getItem('usuario');
            const rol = sessionStorage.getItem('rol');

            // Si no hay sesión, al login
            if (!usuarioStr) return <Redirect to="/" />;
            // Si es alumno e intenta entrar a rutas de admin, al portal
            if (rol !== 'admin') return <Redirect to="/portal" />; 

            return (
              <IonSplitPane contentId="main" style={{ '--side-max-width': '250px' }}>
                <Menu />
                <IonRouterOutlet id="main">
                  <Route path="/dashboard" exact={true}><Dashboard /></Route>
                  <Route path="/carreras" exact={true}><Carreras /></Route>
                  <Route path="/grupos" exact={true}><Grupos /></Route>
                  <Route path="/autores" exact={true}><Autores /></Route>
                  <Route path="/editoriales" exact={true}><Editoriales /></Route>
                  <Route path="/catalogo" exact={true}><Catalogo /></Route>
                  <Route path="/inventario" exact={true}><Inventario /></Route>
                  <Route path="/prestamos" exact={true}><Prestamos /></Route>
                  <Route path="/sanciones" exact={true}><Sanciones /></Route>
                  <Route path="/usuarios" exact={true}><Usuarios /></Route>
                  <Route path="/configuracion" component={Configuracion} exact />
                </IonRouterOutlet>
              </IonSplitPane>
            );
          }} />

        </IonRouterOutlet>
      </IonReactRouter>
    </IonApp>
  );
};

export default App;
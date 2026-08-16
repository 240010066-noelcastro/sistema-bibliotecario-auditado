import React, { useState, useEffect } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
import { libraryOutline, cardOutline, callOutline, peopleOutline, schoolOutline, arrowBackOutline } from 'ionicons/icons';
// @ts-ignore
import api from '../../services/api';
import './CompletarRegistro.css'; 

const CompletarRegistro: React.FC = () => {
  const [matricula, setMatricula] = useState('');
  const [telefono, setTelefono] = useState('');
  const [carreraSeleccionada, setCarreraSeleccionada] = useState(''); 
  const [grupoId, setGrupoId] = useState(''); 
  
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [tokenValido, setTokenValido] = useState(false);
  
  const [grupos, setGrupos] = useState<any[]>([]); 

  // Extrae la lista única de carreras a partir de los grupos
  const carrerasDisponibles = Array.from(
    new Map(
      grupos
        .filter((g: any) => g.Carrera_ID || g.carrera?.Carrera_ID)
        .map((g: any) => [
          g.Carrera_ID || g.carrera?.Carrera_ID,
          {
            id: String(g.Carrera_ID || g.carrera?.Carrera_ID),
            nombre: g.NombreCarrera || g.carrera?.NombreCarrera || g.carrera?.Nombre || `Carrera ${g.Carrera_ID}`
          }
        ])
    ).values()
  );

  // Filtra los grupos según la carrera elegida
  const gruposFiltrados = grupos.filter((g: any) => {
    const cId = String(g.Carrera_ID || g.carrera?.Carrera_ID);
    return cId === carreraSeleccionada;
  });

  useEffect(() => {
    const registroToken = sessionStorage.getItem('registro_token');
    if (registroToken) {
      setTokenValido(true);
      cargarGrupos(); 
    } else {
      window.location.href = '/'; 
    }
  }, []);

  const cargarGrupos = async () => {
    try {
      const response = await api.get('/grupos-publicos?all=true');
      const lista = Array.isArray(response.data?.data) ? response.data.data : [];
      setGrupos(lista);
    } catch (error) {
      console.error("No se pudieron cargar los grupos", error);
      setErrorMsg('Hubo un problema al cargar la lista de grupos.');
    }
  };

  // FUNCIÓN NUEVA: Filtra para que SOLO acepte números y un MÁXIMO de 10 dígitos
  const handleTelefonoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const soloNumeros = e.target.value.replace(/\D/g, ''); // Elimina todo lo que no sea número
    if (soloNumeros.length <= 10) {
      setTelefono(soloNumeros);
    }
  };

  const handleRegistro = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');

    if (!matricula.trim() || !telefono.trim()) {
      return setErrorMsg('Por favor, ingresa tu matrícula/núm. empleado y teléfono.');
    }

    if (telefono.length !== 10) {
      return setErrorMsg('El número de teléfono debe tener exactamente 10 dígitos.');
    }

    if (!carreraSeleccionada) {
      return setErrorMsg('Por favor, selecciona tu carrera o tu perfil institucional.');
    }

    // Obligatorio para estudiantes
    if (carreraSeleccionada !== 'docente' && !grupoId) {
      return setErrorMsg('Por favor, selecciona el grupo al que perteneces.');
    }

    const registroToken = sessionStorage.getItem('registro_token');
    if (!registroToken) {
      setErrorMsg('La sesión de registro expiró. Vuelve a iniciar con Google.');
      setTimeout(() => { window.location.href = '/'; }, 2000);
      return;
    }

    setLoading(true);
    try {
      const payload = {
        registro_token: registroToken,
        matricula: matricula.trim(),
        telefono: telefono.trim(),
        grupo_id: carreraSeleccionada === 'docente' ? null : parseInt(grupoId, 10)
      };

      const response = await api.post('/completar-registro', payload);

      if (response.data.success) {
        sessionStorage.removeItem('registro_token');
        sessionStorage.setItem('usuario', JSON.stringify(response.data.usuario));
        sessionStorage.setItem('rol', 'usuario'); 
        window.location.href = '/portal'; 
      }
    } catch (error: any) {
      console.error(error);
      
      // FILTRO DE ERRORES INTELIGENTE: Evita que salgan textos de código o base de datos
      if (error.response?.data?.errors) {
        // Por si Laravel regresa errores de validación del Request estructurados
        const primerError: any = Object.values(error.response.data.errors)[0];
        setErrorMsg(Array.isArray(primerError) ? primerError[0] : 'Datos inválidos.');
      } else if (error.response?.data?.message) {
        const mensajeServidor = error.response.data.message;
        
        // Si el mensaje contiene palabras raras de base de datos como SQLSTATE o Truncated, lo ocultamos
        if (mensajeServidor.includes('SQLSTATE') || mensajeServidor.includes('truncated') || mensajeServidor.includes('database')) {
          setErrorMsg('Error interno en el servidor. Por favor, asegúrate de que los datos tengan el formato correcto.');
        } else {
          setErrorMsg(mensajeServidor);
        }
      } else {
        setErrorMsg('Error de conexión o problema interno en el servidor.');
      }
    } finally {
      setLoading(false);
    }
  };

  const cancelarRegistro = () => {
    sessionStorage.removeItem('registro_token');
    window.location.href = '/';
  };

  if (!tokenValido) return null;

  return (
    <IonPage>
      <IonContent className="registro-bg" fullscreen>
        
        <button className="btn-back-corner" onClick={cancelarRegistro} disabled={loading}>
          <IonIcon icon={arrowBackOutline} /> Volver al Login
        </button>

        <div className="registro-container">
          
          <div className="registro-left-panel">
            <div className="registro-branding">
              <div className="registro-icon">
                <IonIcon icon={libraryOutline} />
              </div>
              <h1>¡Casi listo!</h1>
              <p>Solo necesitamos tu matrícula, teléfono y grupo para vincular tu cuenta institucional al sistema de la biblioteca.</p>
            </div>
          </div>

          <div className="registro-right-panel">
            <div className="registro-form-box">
              
              <div className="mobile-branding">
                <IonIcon icon={libraryOutline} className="mobile-icon" />
                <h2>UPVE Biblioteca</h2>
              </div>

              <h2 className="registro-title">Completa tu perfil</h2>
              <p className="registro-subtitle">Introduce tus datos institucionales para finalizar tu registro.</p>

              {errorMsg && <div className="registro-alert">{errorMsg}</div>}

              <form onSubmit={handleRegistro}>
                
                <div className="registro-input-group">
                  <label>Matrícula / Núm. de empleado</label>
                  <div className="registro-input-wrapper">
                    <IonIcon icon={cardOutline} className="registro-field-icon" />
                    <input 
                      type="text" 
                      value={matricula}
                      onChange={(e) => setMatricula(e.target.value)}
                      placeholder="Ej. 10203040"
                      disabled={loading}
                    />
                  </div>
                </div>

                <div className="registro-input-group">
                  <label>Teléfono</label>
                  <div className="registro-input-wrapper">
                    <IonIcon icon={callOutline} className="registro-field-icon" />
                    <input 
                      type="text"  /* Cambiado a text para mejorar el control del formateo en vivo */
                      value={telefono}
                      onChange={handleTelefonoChange} /* <--- Usamos la nueva función controlada */
                      placeholder="A 10 dígitos"
                      disabled={loading}
                    />
                  </div>
                </div>

                {/* SELECTOR DE CARRERA / PERFIL */}
                <div className="registro-input-group">
                  <label>Carrera / Perfil Institucional *</label>
                  <div className="registro-input-wrapper">
                    <IonIcon icon={schoolOutline} className="registro-field-icon" />
                    <select 
                      value={carreraSeleccionada}
                      onChange={(e) => {
                        setCarreraSeleccionada(e.target.value);
                        setGrupoId(''); 
                      }}
                      disabled={loading}
                    >
                      <option value="">Selecciona tu carrera o perfil...</option>
                      {carrerasDisponibles.map((carrera: any) => (
                        <option key={carrera.id} value={carrera.id}>
                          {carrera.nombre}
                        </option>
                      ))}
                      <option value="docente">Personal Docente / Administrativo</option>
                    </select>
                  </div>
                </div>

                {/* SELECTOR DE GRUPO (Solo si es estudiante con carrera seleccionada) */}
                {carreraSeleccionada && carreraSeleccionada !== 'docente' && (
                  <div className="registro-input-group">
                    <label>Grupo Asignado *</label>
                    <div className="registro-input-wrapper">
                      <IonIcon icon={peopleOutline} className="registro-field-icon" />
                      <select 
                        value={grupoId}
                        onChange={(e) => setGrupoId(e.target.value)}
                        disabled={loading}
                      >
                        <option value="">Selecciona tu grupo...</option>
                        {gruposFiltrados.map((grupo: any) => (
                          <option key={grupo.Grupo_ID || grupo.id} value={grupo.Grupo_ID || grupo.id}>
                            {grupo.NombreGrupo || grupo.Nombre || grupo.nombre || `Grupo ${grupo.Grupo_ID || grupo.id}`}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>
                )}

                <button type="submit" className="btn-registro-submit" disabled={loading}>
                  {loading ? 'Finalizando Registro...' : 'Finalizar Registro'}
                </button>
              </form>

              <button className="btn-back-mobile" onClick={cancelarRegistro} disabled={loading}>
                <IonIcon icon={arrowBackOutline} /> Cancelar y volver
              </button>

            </div>
          </div>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default CompletarRegistro;
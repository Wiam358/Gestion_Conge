
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/google-calendar@6.1.8/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    /* Style minimaliste pour le calendrier - Thème Seagreen */
    #calendar {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 15px;
        margin-top: 20px;
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    /* En-tête du calendrier */
    .fc-header-toolbar {
        padding: 10px 5px;
        margin-bottom: 10px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .fc-toolbar-title {
        font-size: 1.1em;
        color: #2e8b57;
        font-weight: 600;
    }

    /* Boutons de navigation */
    .fc-button {
        background-color: #3cb371;
        border: none;
        padding: 5px 10px;
        font-size: 0.85em;
        transition: all 0.3s ease;
        transform: translateY(0);
    }

    .fc-button:hover {
        background-color: #2e8b57;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46, 139, 87, 0.3);
    }

    /* Jours de la semaine */
    .fc-col-header-cell {
        background-color: #f8f9fa;
        color: #2e8b57;
        padding: 8px 2px;
        font-size: 0.85em;
    }

    /* Cellules des jours */
    .fc-daygrid-day {
        border-color: #e9ecef;
        transition: transform 0.2s ease;
    }

    .fc-daygrid-day:hover {
        transform: translateY(-2px);
    }

    .fc-daygrid-day-number {
        color: #495057;
        font-size: 0.85em;
        padding: 2px;
    }

    /* Jour actuel */
    .fc-day-today {
        background-color: #e8f5e9;
        animation: pulseToday 2s;
    }

    /* Événements */
    .fc-event {
        background-color: #3cb371;
        border: none;
        border-radius: 2px;
        padding: 1px 3px;
        font-size: 0.8em;
        margin: 0 1px 1px 0;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        animation: eventPopIn 0.4s ease-out forwards;
        opacity: 0;
        transform: scale(0.95);
    }

    .fc-event:hover {
        background-color: #2e8b57;
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Modal */
    .modal-header {
        background-color: #2e8b57;
        color: white;
    }

    .btn-primary {
        background-color: #2e8b57;
        border-color: #2e8b57;
    }

    .btn-primary:hover {
        background-color: #1e5631;
        border-color: #1e5631;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }

    .modal-footer .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .modal-footer .btn-danger:hover {
        background-color: #bb2d3b;
        border-color: #bb2d3b;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .fc-header-toolbar {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .fc-toolbar-chunk {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes eventPopIn {
        0% {
            opacity: 0;
            transform: scale(0.95);
        }
        80% {
            opacity: 0.8;
            transform: scale(1.02);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes pulseToday {
        0% {
            box-shadow: 0 0 0 0 rgba(142, 212, 168, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(142, 212, 168, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(142, 212, 168, 0);
        }
    }

    .fc-view-harness {
        transition: opacity 0.3s ease;
    }

    .fc-view-harness-active {
        animation: viewFadeIn 0.4s ease-out;
    }

    @keyframes viewFadeIn {
        from {
            opacity: 0.5;
            transform: translateY(5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
  </style>
</head>
<body>
  <h2 class="mb-4 fw-bold" style="color: #2e8b57;"><i class="bi bi-calendar-fill"></i> Calendrier</h2>
  <div id="calendar"></div>

  <!-- Modal d'ajout d'événement -->
  <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="addEventForm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addEventModalLabel">Ajouter un événement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body">
            <div class="row gx-3">
              <div class="col-md-6">
                <label for="addEventTitle" class="form-label">Titre</label>
                <input type="text" class="form-control" id="addEventTitle" required>
              </div>
              <div class="col-md-6">
                <label for="addEventType" class="form-label">Type d'événement</label>
                <select id="addEventType" class="form-select" required>
                  <option value="" selected disabled>Choisir</option>
                  <option value="congé">Congé</option>
                  <option value="formation">Formation</option>
                  <option value="réunion">Réunion</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
            </div>
            <div class="row gx-3">
              <div class="col-md-6">
                <label for="addEventDate" class="form-label">Date de début</label>
                <input type="date" class="form-control" id="addEventDate" required>
              </div>
              <div class="col-md-6">
                <label for="addEventEndDate" class="form-label">Date de fin</label>
                <input type="date" class="form-control" id="addEventEndDate" required>
              </div>
            </div>
            <div class="row gx-3">
              <div class="mb-3">
                <label for="addStartTime" class="form-label">Heure de début</label>
                <input type="time" class="form-control" id="addStartTime" required>
              </div>
              <div class="mb-3">
                <label for="addEndTime" class="form-label">Heure de fin</label>
                <input type="time" class="form-control" id="addEndTime" required>
              </div>
            </div>
            <div class="row gx-3">
              <div class="mb-3">
                <label for="addEventVisibility" class="form-label">Visibilité</label>
                <select id="addEventVisibility" class="form-select" required>
                  <option value="public" selected>Public</option>
                  <option value="private">Privé</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label for="addEventDescription" class="form-label">Description</label>
              <textarea class="form-control" id="addEventDescription" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-primary">Ajouter</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de modification d'événement -->
  <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="editEventForm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editEventModalLabel">Modifier l'événement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body">
            <div class="row gx-3">
              <div class="col-md-6">
                <label for="editEventTitle" class="form-label">Titre</label>
                <input type="text" class="form-control" id="editEventTitle" required>
              </div>
              <div class="col-md-6">
                <label for="editEventType" class="form-label">Type d'événement</label>
                <select id="editEventType" class="form-select" required>
                  <option value="" selected disabled>Choisir</option>
                  <option value="congé">Congé</option>
                  <option value="formation">Formation</option>
                  <option value="réunion">Réunion</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
            </div>
            <div class="row gx-3">
              <div class="col-md-6">
                <label for="editEventDate" class="form-label">Date de début</label>
                <input type="date" class="form-control" id="editEventDate" required>
              </div>
              <div class="col-md-6">
                <label for="editEventEndDate" class="form-label">Date de fin</label>
                <input type="date" class="form-control" id="editEventEndDate" required>
              </div>
            </div>
            <div class="row gx-3">
              <div class="mb-3">
                <label for="editStartTime" class="form-label">Heure de début</label>
                <input type="time" class="form-control" id="editStartTime" required>
              </div>
              <div class="mb-3">
                <label for="editEndTime" class="form-label">Heure de fin</label>
                <input type="time" class="form-control" id="editEndTime" required>
              </div>
            </div>
            <div class="row gx-3">
              <div class="mb-3">
                <label for="editEventVisibility" class="form-label">Visibilité</label>
                <select id="editEventVisibility" class="form-select" required>
                  <option value="public">Public</option>
                  <option value="private">Privé</option>
                </select>
              </div>
            </div>
            <div class="mb-3">
              <label for="editEventDescription" class="form-label">Description</label>
              <textarea class="form-control" id="editEventDescription" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" id="deleteBtn" class="btn btn-danger me-auto">Supprimer</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-warning">Modifier</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Vérifier si Bootstrap est chargé
    if (typeof bootstrap === 'undefined') {
      console.error('Bootstrap non chargé');
      Swal.fire('Erreur', 'Problème de chargement des dépendances.', 'error');
    }

    document.addEventListener('DOMContentLoaded', function () {
      let calendarEl = document.getElementById('calendar');
      let currentEventId = null;
      const utilisateurConnecte = '<?php echo htmlspecialchars($_SESSION["cin"] ?? "", ENT_QUOTES, "UTF-8"); ?>';

      let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        selectable: true,
        // Note: Stocker la clé API côté serveur pour plus de sécurité
        googleCalendarApiKey: 'AIzaSyAtqhp1afQG7EiPT_U5DeBFfcqlW11tcM4',

        eventSources: [
          {
            url: 'load-events.php',
            failure: () => {
              Swal.fire('Erreur', 'Impossible de charger les événements.', 'error');
            }
          },
          {
            googleCalendarId: 'fr.french#holiday@group.v.calendar.google.com',
            className: 'ferie-event',
            color: '#ff6666',
            textColor: '#fff'
          },
          {
            googleCalendarId: 'en.islamic#holiday@group.v.calendar.google.com',
            color: '#b39ddb',
            textColor: 'black',
            className: 'holiday-event'
          }
        ],
        dateClick: function (info) {
          document.getElementById('addEventDate').value = info.dateStr;
          document.getElementById('addEventEndDate').value = info.dateStr;
          new bootstrap.Modal(document.getElementById('addEventModal')).show();
        },
        eventClick: function(info) {
          const event = info.event;
          const createdBy = event.extendedProps.created_by;
          const currentUser = utilisateurConnecte;

          if (createdBy !== currentUser) {
            Swal.fire({
              icon: 'error',
              title: 'Accès refusé',
              text: 'Seul le créateur peut modifier cet événement.',
              confirmButtonColor: '#d33'
            });
            return;
          }

          currentEventId = event.id;
          document.getElementById('editEventTitle').value = event.title;
          document.getElementById('editEventDescription').value = event.extendedProps.description || '';
          document.getElementById('editEventType').value = event.extendedProps.event_type || 'autre';
          document.getElementById('editEventVisibility').value = event.extendedProps.visibility || 'public';
          
          const start = new Date(event.start);
          const end = event.end ? new Date(event.end) : new Date(event.start);
          
          document.getElementById('editEventDate').value = start.toISOString().split('T')[0];
          document.getElementById('editEventEndDate').value = end.toISOString().split('T')[0];
          document.getElementById('editStartTime').value = start.toTimeString().substring(0, 5);
          document.getElementById('editEndTime').value = end.toTimeString().substring(0, 5);

          new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }
      });

      calendar.render();

      // Gestion de l'ajout d'événement
      document.getElementById('addEventForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const title = document.getElementById('addEventTitle').value.trim();
        const description = document.getElementById('addEventDescription').value.trim();
        const eventType = document.getElementById('addEventType').value;
        const visibility = document.getElementById('addEventVisibility').value;
        const startDate = document.getElementById('addEventDate').value;
        const endDate = document.getElementById('addEventEndDate').value;
        const startTime = document.getElementById('addStartTime').value;
        const endTime = document.getElementById('addEndTime').value;

        if (!title || !startDate || !endDate || !startTime || !endTime || !eventType) {
          Swal.fire('Erreur', 'Veuillez remplir tous les champs obligatoires', 'error');
          return;
        }

        const startDateTime = `${startDate}T${startTime}`;
        const endDateTime = `${endDate}T${endTime}`;

        if (new Date(startDateTime) > new Date(endDateTime)) {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'La date/heure de fin doit être après celle de début.'
          });
          return;
        }

        fetch('add-event.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            title: title,
            description: description,
            event_type: eventType,
            start: startDateTime,
            end: endDateTime,
            visibility: visibility,
          })
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Erreur réseau');
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            calendar.addEvent({
              id: data.event.id,
              title: data.event.title,
              start: data.event.start,
              end: data.event.end,
              extendedProps: {
                description: data.event.description,
                event_type: data.event.event_type,
                created_by: data.event.created_by,
                visibility: data.event.visibility
              },
              backgroundColor: data.event.backgroundColor,
              textColor: data.event.textColor
            });

            Swal.fire({
              icon: 'success',
              title: 'Succès',
              text: 'Événement ajouté avec succès !'
            });
            
            document.getElementById('addEventForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('addEventModal')).hide();
          } else {
            throw new Error(data.error || "Erreur lors de l'ajout de l'événement");
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: error.message
          });
        });
      });

      // Gestion de la modification d'événement
      document.getElementById('editEventForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const title = document.getElementById('editEventTitle').value.trim();
        const description = document.getElementById('editEventDescription').value.trim();
        const eventType = document.getElementById('editEventType').value;
        const visibility = document.getElementById('editEventVisibility').value;
        const startDate = document.getElementById('editEventDate').value;
        const endDate = document.getElementById('editEventEndDate').value;
        const startTime = document.getElementById('editStartTime').value;
        const endTime = document.getElementById('editEndTime').value;

        if (!title || !startDate || !endDate || !startTime || !endTime || !eventType) {
          Swal.fire('Erreur', 'Veuillez remplir tous les champs obligatoires', 'error');
          return;
        }

        const startDateTime = `${startDate}T${startTime}`;
        const endDateTime = `${endDate}T${endTime}`;

        if (new Date(startDateTime) > new Date(endDateTime)) {
          Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: 'La date/heure de fin doit être après celle de début.'
          });
          return;
        }

        fetch('update-event.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: currentEventId,
            title: title,
            description: description,
            event_type: eventType,
            start: startDateTime,
            end: endDateTime,
            visibility: visibility,
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Succès', 'Événement modifié avec succès!', 'success');
            calendar.refetchEvents();
            bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
          } else {
            throw new Error(data.error || "Erreur lors de la modification");
          }
        })
        .catch(error => {
          Swal.fire('Erreur', error.message, 'error');
        });
      });

      document.getElementById('deleteBtn').addEventListener('click', function() {
        Swal.fire({
          title: 'Confirmer la suppression',
          text: "Êtes-vous sûr de vouloir supprimer cet événement?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Oui, supprimer!'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('delete-event.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
              },
              body: JSON.stringify({ id: currentEventId })
            })
            .then(response => {
              const contentType = response.headers.get('content-type');
              if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                  throw new Error(`Réponse inattendue: ${text}`);
                });
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                const event = calendar.getEventById(currentEventId);
                if (event) event.remove();
                
                Swal.fire('Succès!', data.message || 'Événement supprimé', 'success');
                bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
              } else {
                throw new Error(data.error || "Échec de la suppression");
              }
            })
            .catch(error => {
              console.error('Erreur:', error);
              Swal.fire('Erreur', error.message, 'error');
            });
          }
        });
      });
    });
  </script>
</body>
</html>
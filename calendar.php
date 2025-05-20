<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/google-calendar@6.1.8/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    /* Style minimaliste pour le calendrier - Thème Seagreen */
    #calendar {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 15px;
        margin-top: 20px;
    }

    /* En-tête du calendrier */
    .fc-header-toolbar {
        padding: 10px 5px;
        margin-bottom: 10px !important;
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
        background-color: #3cb371 !important;
        border: none !important;
        padding: 5px 10px !important;
        font-size: 0.85em !important;
    }

    .fc-button:hover {
        background-color: #2e8b57 !important;
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
        border-color: #e9ecef !important;
    }

    .fc-daygrid-day-number {
        color: #495057;
        font-size: 0.85em;
        padding: 2px !important;
    }

    /* Jour actuel */
    .fc-day-today {
        background-color: #e8f5e9 !important;
    }

    /* Événements */
    .fc-event {
        background-color: #3cb371;
        border: none;
        border-radius: 2px;
        padding: 1px 3px;
        font-size: 0.8em;
        margin: 0 1px 1px 0 !important;
    }

    .fc-event:hover {
        background-color: #2e8b57;
    }

    /* Modal - Sans affecter le reste du dashboard */
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
    /* ANIMATIONS POUR LE CALENDRIER */
    #calendar {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    .fc-event {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        animation: eventPopIn 0.4s ease-out forwards;
        opacity: 0;
        transform: scale(0.95);
    }

    .fc-daygrid-day:hover {
        transform: translateY(-2px);
        transition: transform 0.2s ease;
    }

    .fc-day-today {
        animation: pulseToday 2s infinite;
    }

    /* Animation pour les boutons */
    .fc-button {
        transition: all 0.3s ease !important;
        transform: translateY(0);
    }

    .fc-button:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 8px rgba(46, 139, 87, 0.3) !important;
    }

    /* Animation au chargement */
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

    /* Animation pour les événements */
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

    /* Animation pour le jour actuel */
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

    /* Animation au changement de vue */
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

    /* Animation pour les événements au survol */
    .fc-event:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    .modal-footer .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .modal-footer .btn-danger:hover {
        background-color: #bb2d3b;
        border-color: #bb2d3b;
    }
  </style>
</head>
<body>

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
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let calendarEl = document.getElementById('calendar');
      let currentEventId = null;

      let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        selectable: true,
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
          currentEventId = info.event.id;
          document.getElementById('editEventTitle').value = info.event.title;
          document.getElementById('editEventDescription').value = info.event.extendedProps.description || '';
          document.getElementById('editEventType').value = info.event.extendedProps.event_type || 'autre';
          document.getElementById('editEventVisibility').value = info.event.extendedProps.visibility || 'public';
          
          const startDate = new Date(info.event.start);
          const endDate = info.event.end ? new Date(info.event.end) : new Date(info.event.start);
          
          document.getElementById('editEventDate').value = startDate.toISOString().split('T')[0];
          document.getElementById('editEventEndDate').value = endDate.toISOString().split('T')[0];
          document.getElementById('editStartTime').value = startDate.toTimeString().substring(0, 5);
          document.getElementById('editEndTime').value = endDate.toTimeString().substring(0, 5);
          
          new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }
      });

      calendar.render();

      // Gestion de l'ajout d'événement
      document.getElementById('addEventForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const title = document.getElementById('addEventTitle').value;
        const description = document.getElementById('addEventDescription').value;
        const eventType = document.getElementById('addEventType').value;
        const visibility = document.getElementById('addEventVisibility').value;
        const startDate = document.getElementById('addEventDate').value;
        const endDate = document.getElementById('addEventEndDate').value;
        const startTime = document.getElementById('addStartTime').value;
        const endTime = document.getElementById('addEndTime').value;

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
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Succès',
              text: 'Événement ajouté avec succès !'
            });
            calendar.refetchEvents();
            document.getElementById('addEventForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('addEventModal')).hide();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Erreur',
              text: "Erreur lors de l'ajout de l'événement."
            });
          }
        });
      });

      // Gestion de la modification d'événement
      document.getElementById('editEventForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const title = document.getElementById('editEventTitle').value;
        const description = document.getElementById('editEventDescription').value;
        const eventType = document.getElementById('editEventType').value;
        const visibility = document.getElementById('editEventVisibility').value;
        const startDate = document.getElementById('editEventDate').value;
        const endDate = document.getElementById('editEventEndDate').value;
        const startTime = document.getElementById('editStartTime').value;
        const endTime = document.getElementById('editEndTime').value;

        const startDateTime = `${startDate}T${startTime}`;
        const endDateTime = `${endDate}T${endTime}`;

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
            Swal.fire('Erreur', data.error || "Erreur lors de la modification", 'error');
          }
        });
      });

      // Gestion de la suppression d'événement
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
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: currentEventId })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Supprimé!', 'L\'événement a été supprimé.', 'success');
                calendar.refetchEvents();
                bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
              } else {
                Swal.fire('Erreur', data.error || "Erreur lors de la suppression", 'error');
              }
            });
          }
        });
      });
    });
  </script>
</body>
</html>
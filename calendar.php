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
#eventModal .modal-header {
    background-color: #2e8b57;
    color: white;
}

#eventModal .btn-primary {
    background-color: #2e8b57;
    border-color: #2e8b57;
}

#eventModal .btn-primary:hover {
    background-color: #1e5631;
    border-color: #1e5631;
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
  </style>
</head>
<body>

  
  <div id="calendar"></div>

  <!-- Modal d'ajout d'événement -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="eventForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eventModalLabel">Ajouter un événement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
        <div class="row gx-3">
            <div class="col-md-6">
                <label for="eventTitle" class="form-label">Titre</label>
                <input type="text" class="form-control" id="eventTitle" required>
            </div>
            <div class="col-md-6">
                <label for="eventType" class="form-label">Type d'événement</label>
                <select id="eventType" class="form-select" required>
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
                <label for="eventDate" class="form-label">Date de début</label>
                <input type="date" class="form-control" id="eventDate" required>
            </div>

            <div class="col-md-6">
                <label for="eventEndDate" class="form-label">Date de fin</label>
                <input type="date" class="form-control" id="eventEndDate" required>
            </div>
        </div>
        <div class="row gx-3">
            <div class="mb-3">
                <label for="startTime" class="form-label">Heure de début</label>
                <input type="time" class="form-control" id="startTime" required>
            </div>

            <div class="mb-3">
                <label for="endTime" class="form-label">Heure de fin</label>
                <input type="time" class="form-control" id="endTime" required>
            </div>
        </div>


        <div class="mb-3">
            <label for="eventDescription" class="form-label">Description</label>
            <textarea class="form-control" id="eventDescription" rows="3"></textarea>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Ajouter</button>
        </div>
      </div>
    </form>
  </div>
</div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let calendarEl = document.getElementById('calendar'); // <-- Défini calendarEl ici

      let calendar = new FullCalendar.Calendar(calendarEl, {
        
        initialView: 'dayGridMonth',
        locale: 'fr',
        selectable: true,
        events: 'load-events.php', // Charger les événements depuis la BDD
        googleCalendarApiKey: 'AIzaSyAtqhp1afQG7EiPT_U5DeBFfcqlW11tcM4',

        eventSources: [
    {
      url: 'load-events.php', // tes événements dynamiques
      failure: () => {
        Swal.fire('Erreur', 'Impossible de charger les événements.', 'error');
      }
    },
    {
      googleCalendarId: 'fr.french#holiday@group.v.calendar.google.com', // ou un autre calendrier selon ton pays
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
          document.getElementById('eventDate').value = info.dateStr;
          new bootstrap.Modal(document.getElementById('eventModal')).show();
        }
      });

      calendar.render();

      document.getElementById('eventForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const title = document.getElementById('eventTitle').value;
  const description = document.getElementById('eventDescription').value;
  const eventType = document.getElementById('eventType').value;

  const startDate = document.getElementById('eventDate').value;
  const endDate = document.getElementById('eventEndDate').value;
  const startTime = document.getElementById('startTime').value;
  const endTime = document.getElementById('endTime').value;

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
      end: endDateTime
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
    } else {
       Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: "Erreur lors de l'ajout de l'événement."
      });
    }
  });

  document.getElementById('eventForm').reset();
  bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
});
    });
  </script>
</body>
</html>

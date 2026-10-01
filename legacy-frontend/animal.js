document.addEventListener("DOMContentLoaded", function () {

  const loginLink =
    document.getElementById("loginLink");

  const loginModal =
    document.getElementById("loginModal");

  const closeLoginModal =
    document.getElementById("closeLoginModal");

  const accountTypeCards =
    document.querySelectorAll(".account-type-card");

  const accountContinueBtn =
    document.getElementById("accountContinueBtn");

  let selectedAccountType = "";


  if (loginLink && loginModal) {

    loginLink.addEventListener(
      "click",
      function (event) {

        event.preventDefault();

        loginModal.style.display = "flex";

      }
    );

  }


  if (closeLoginModal && loginModal) {

    closeLoginModal.addEventListener(
      "click",
      function () {

        loginModal.style.display = "none";

      }
    );

  }


  if (loginModal) {

    loginModal.addEventListener(
      "click",
      function (event) {

        if (event.target === loginModal) {

          loginModal.style.display = "none";

        }

      }
    );

  }


  accountTypeCards.forEach(
    function (card) {

      card.addEventListener(
        "click",
        function () {

          accountTypeCards.forEach(
            function (item) {

              item.classList.remove(
                "selected"
              );

            }
          );

          card.classList.add("selected");

          selectedAccountType =
            card.dataset.role;

          if (accountContinueBtn) {

            accountContinueBtn.disabled =
              false;

          }

        }
      );

    }
  );


if (accountContinueBtn) {

  accountContinueBtn.addEventListener(
    "click",
    function () {

      if (!selectedAccountType) {

        return;

      }

      if (
        selectedAccountType === "owner"
      ) {

        window.location.href =
          "login.html?role=owner";

        return;

      }

      if (
        selectedAccountType === "assistant"
      ) {

        window.location.href =
          "login.html?role=assistant";

        return;

      }

      if (
        selectedAccountType === "admin"
      ) {

        window.location.href =
          "adminlogin.html";

        return;

      }

      if (
        selectedAccountType === "superadmin"
      ) {

        window.location.href =
          "superadminlogin.html";

        return;

      }

    }
  );

}


  const loginForm =
    document.getElementById("loginForm");


  if (loginForm) {

    loginForm.addEventListener(
      "submit",
      function (event) {

        event.preventDefault();

        const email =
          document.getElementById("email").value.trim();

        const password =
          document.getElementById("password").value;

        const role =
          new URLSearchParams(
            window.location.search
          ).get("role");


        if (
          role === "owner" &&
          email === "owner@fmhanimalclinic.com" &&
          password === "owner123"
        ) {

          localStorage.setItem(
            "userRole",
            "owner"
          );

          localStorage.setItem(
            "userEmail",
            email
          );

          window.location.href =
            "dashboard.html";

          return;

        }


        if (
          role === "assistant" &&
          email === "assistant@fmhanimalclinic.com" &&
          password === "assistant123"
        ) {

          localStorage.setItem(
            "userRole",
            "assistant"
          );

          localStorage.setItem(
            "userEmail",
            email
          );

          window.location.href =
            "assistantdashboard.html";

          return;

        }


        alert(
          "Invalid email, password, or account type."
        );

      }
    );

  }


  const newAppointmentBtn =
    document.getElementById(
      "newAppointmentBtn"
    );

  const appointmentModal =
    document.getElementById(
      "appointmentModal"
    );

  const closeAppointmentModal =
    document.getElementById(
      "closeAppointmentModal"
    );

  const cancelAppointmentBtn =
    document.getElementById(
      "cancelAppointmentBtn"
    );

  const appointmentForm =
    document.getElementById(
      "appointmentForm"
    );

  const appointmentTable =
    document.getElementById(
      "appointmentTable"
    );

  const modalTitle =
    document.getElementById(
      "modalTitle"
    );

  const modalSubtitle =
    document.getElementById(
      "modalSubtitle"
    );

  const ownerName =
    document.getElementById(
      "ownerName"
    );

  const petName =
    document.getElementById(
      "petName"
    );

  const serviceName =
    document.getElementById(
      "serviceName"
    );

  const appointmentDate =
    document.getElementById(
      "appointmentDate"
    );

  const appointmentStatus =
    document.getElementById(
      "appointmentStatus"
    );

  const viewModal =
    document.getElementById(
      "viewModal"
    );

  const closeViewModal =
    document.getElementById(
      "closeViewModal"
    );

  const viewCloseBtn =
    document.getElementById(
      "viewCloseBtn"
    );

  const viewOwner =
    document.getElementById(
      "viewOwner"
    );

  const viewPet =
    document.getElementById(
      "viewPet"
    );

  const viewService =
    document.getElementById(
      "viewService"
    );

  const viewDate =
    document.getElementById(
      "viewDate"
    );

  const viewStatus =
    document.getElementById(
      "viewStatus"
    );

  const appointmentSearch =
    document.getElementById(
      "appointmentSearch"
    );

  const statusFilter =
    document.getElementById(
      "statusFilter"
    );

  const serviceFilter =
    document.getElementById(
      "serviceFilter"
    );

  let editingRow = null;


  if (
    newAppointmentBtn &&
    appointmentModal &&
    appointmentForm &&
    appointmentStatus
  ) {

    function openNewAppointmentModal() {

      editingRow = null;

      if (modalTitle) {

        modalTitle.textContent =
          "New Appointment";

      }

      if (modalSubtitle) {

        modalSubtitle.textContent =
          "Create a new appointment for a pet.";

      }

      appointmentForm.reset();

      appointmentStatus.value =
        "Pending";

      appointmentModal.classList.add(
        "show"
      );

    }


    function closeAppointmentForm() {

      appointmentModal.classList.remove(
        "show"
      );

      editingRow = null;

      appointmentForm.reset();

    }


    function formatDate(dateValue) {

      if (!dateValue) {

        return "";

      }

      const date =
        new Date(
          dateValue + "T00:00:00"
        );

      return date.toLocaleDateString(
        "en-US",
        {
          month: "long",
          day: "numeric",
          year: "numeric"
        }
      );

    }


    function getDateValue(dateText) {

      const date =
        new Date(dateText);

      if (
        isNaN(
          date.getTime()
        )
      ) {

        return "";

      }

      const year =
        date.getFullYear();

      const month =
        String(
          date.getMonth() + 1
        ).padStart(2, "0");

      const day =
        String(
          date.getDate()
        ).padStart(2, "0");

      return `${year}-${month}-${day}`;

    }


    function getStatusClass(status) {

      return status.toLowerCase();

    }


    function createAppointmentRow(
      owner,
      pet,
      service,
      date,
      status
    ) {

      const row =
        document.createElement("tr");

      row.innerHTML = `

        <td>
          ${owner}
        </td>

        <td>
          ${pet}
        </td>

        <td>
          ${service}
        </td>

        <td>
          ${formatDate(date)}
        </td>

        <td>

          <span class="status ${getStatusClass(status)}">
            ${status}
          </span>

        </td>

        <td>

          <button
            class="action-view"
            type="button"
          >
            View
          </button>

          <button
            class="action-edit"
            type="button"
          >
            Edit
          </button>

          <button
            class="action-delete"
            type="button"
          >
            Delete
          </button>

        </td>

      `;

      appointmentTable.appendChild(
        row
      );

    }


    function applyFilters() {

      if (
        !appointmentSearch ||
        !statusFilter ||
        !serviceFilter ||
        !appointmentTable
      ) {

        return;

      }

      const searchValue =
        appointmentSearch.value.toLowerCase();

      const selectedStatus =
        statusFilter.value;

      const selectedService =
        serviceFilter.value;

      const rows =
        appointmentTable.querySelectorAll(
          "tr"
        );


      rows.forEach(
        function (row) {

          const owner =
            row.cells[0]
              .textContent
              .toLowerCase();

          const pet =
            row.cells[1]
              .textContent
              .toLowerCase();

          const service =
            row.cells[2]
              .textContent;

          const status =
            row.cells[4]
              .textContent
              .trim();


          const matchesSearch =
            owner.includes(
              searchValue
            ) ||
            pet.includes(
              searchValue
            ) ||
            service
              .toLowerCase()
              .includes(
                searchValue
              );


          const matchesStatus =
            selectedStatus === "all" ||
            status === selectedStatus;


          const matchesService =
            selectedService === "all" ||
            service === selectedService;


          if (
            matchesSearch &&
            matchesStatus &&
            matchesService
          ) {

            row.style.display = "";

          } else {

            row.style.display = "none";

          }

        }
      );

    }


    newAppointmentBtn.addEventListener(
      "click",
      function () {

        openNewAppointmentModal();

      }
    );


    if (closeAppointmentModal) {

      closeAppointmentModal.addEventListener(
        "click",
        function () {

          closeAppointmentForm();

        }
      );

    }


    if (cancelAppointmentBtn) {

      cancelAppointmentBtn.addEventListener(
        "click",
        function () {

          closeAppointmentForm();

        }
      );

    }


    appointmentForm.addEventListener(
      "submit",
      function (event) {

        event.preventDefault();

        const owner =
          ownerName.value.trim();

        const pet =
          petName.value.trim();

        const service =
          serviceName.value;

        const date =
          appointmentDate.value;

        const status =
          appointmentStatus.value;


        if (
          !owner ||
          !pet ||
          !service ||
          !date ||
          !status
        ) {

          alert(
            "Please complete all appointment information."
          );

          return;

        }


        if (editingRow) {

          editingRow.cells[0].textContent =
            owner;

          editingRow.cells[1].textContent =
            pet;

          editingRow.cells[2].textContent =
            service;

          editingRow.cells[3].textContent =
            formatDate(date);

          editingRow.cells[4].innerHTML = `

            <span class="status ${getStatusClass(status)}">
              ${status}
            </span>

          `;

          alert(
            "Appointment updated successfully."
          );

        } else {

          createAppointmentRow(
            owner,
            pet,
            service,
            date,
            status
          );

          alert(
            "New appointment added successfully."
          );

        }


        closeAppointmentForm();

        applyFilters();

      }
    );


    if (appointmentTable) {

      appointmentTable.addEventListener(
        "click",
        function (event) {

          const button =
            event.target;

          const row =
            button.closest("tr");


          if (!row) {

            return;

          }


          if (
            button.classList.contains(
              "action-view"
            )
          ) {

            if (
              viewOwner &&
              viewPet &&
              viewService &&
              viewDate &&
              viewStatus &&
              viewModal
            ) {

              viewOwner.textContent =
                row.cells[0]
                  .textContent
                  .trim();

              viewPet.textContent =
                row.cells[1]
                  .textContent
                  .trim();

              viewService.textContent =
                row.cells[2]
                  .textContent
                  .trim();

              viewDate.textContent =
                row.cells[3]
                  .textContent
                  .trim();

              viewStatus.textContent =
                row.cells[4]
                  .textContent
                  .trim();

              viewModal.classList.add(
                "show"
              );

            }

          }


          if (
            button.classList.contains(
              "action-edit"
            )
          ) {

            editingRow = row;

            if (modalTitle) {

              modalTitle.textContent =
                "Edit Appointment";

            }

            if (modalSubtitle) {

              modalSubtitle.textContent =
                "Update the appointment information.";

            }

            ownerName.value =
              row.cells[0]
                .textContent
                .trim();

            petName.value =
              row.cells[1]
                .textContent
                .trim();

            serviceName.value =
              row.cells[2]
                .textContent
                .trim();

            appointmentDate.value =
              getDateValue(
                row.cells[3]
                  .textContent
                  .trim()
              );

            appointmentStatus.value =
              row.cells[4]
                .textContent
                .trim();

            appointmentModal.classList.add(
              "show"
            );

          }


          if (
            button.classList.contains(
              "action-delete"
            )
          ) {

            const owner =
              row.cells[0]
                .textContent
                .trim();

            const pet =
              row.cells[1]
                .textContent
                .trim();


            const confirmed =
              confirm(
                `Are you sure you want to delete the appointment for ${owner} and ${pet}?`
              );


            if (confirmed) {

              row.remove();

              alert(
                "Appointment deleted successfully."
              );

            }

          }

        }
      );

    }


    if (closeViewModal && viewModal) {

      closeViewModal.addEventListener(
        "click",
        function () {

          viewModal.classList.remove(
            "show"
          );

        }
      );

    }


    if (viewCloseBtn && viewModal) {

      viewCloseBtn.addEventListener(
        "click",
        function () {

          viewModal.classList.remove(
            "show"
          );

        }
      );

    }


    appointmentModal.addEventListener(
      "click",
      function (event) {

        if (
          event.target ===
          appointmentModal
        ) {

          closeAppointmentForm();

        }

      }
    );


    if (viewModal) {

      viewModal.addEventListener(
        "click",
        function (event) {

          if (
            event.target ===
            viewModal
          ) {

            viewModal.classList.remove(
              "show"
            );

          }

        }
      );

    }


    if (appointmentSearch) {

      appointmentSearch.addEventListener(
        "input",
        applyFilters
      );

    }


    if (statusFilter) {

      statusFilter.addEventListener(
        "change",
        applyFilters
      );

    }


    if (serviceFilter) {

      serviceFilter.addEventListener(
        "change",
        applyFilters
      );

    }

  }


  const editAppointmentBtn =
    document.getElementById(
      "editAppointmentBtn"
    );


  if (editAppointmentBtn) {

    editAppointmentBtn.addEventListener(
      "click",
      function () {

        window.location.href =
          "editappointment.html";

      }
    );

  }


  const editAppointmentForm =
    document.getElementById(
      "editAppointmentForm"
    );

  const cancelEditBtn =
    document.getElementById(
      "cancelEditBtn"
    );


  if (cancelEditBtn) {

    cancelEditBtn.addEventListener(
      "click",
      function () {

        window.location.href =
          "adminappointmentdetails.html";

      }
    );

  }


  if (editAppointmentForm) {

    editAppointmentForm.addEventListener(
      "submit",
      function (event) {

        event.preventDefault();


        const ownerElement =
          document.getElementById(
            "editOwnerName"
          );

        const petElement =
          document.getElementById(
            "editPetName"
          );

        const serviceElement =
          document.getElementById(
            "editServiceName"
          );

        const statusElement =
          document.getElementById(
            "editStatus"
          );

        const dateElement =
          document.getElementById(
            "editAppointmentDate"
          );

        const timeElement =
          document.getElementById(
            "editAppointmentTime"
          );

        const reasonElement =
          document.getElementById(
            "editReason"
          );

        const notesElement =
          document.getElementById(
            "editNotes"
          );


        if (
          !ownerElement ||
          !petElement ||
          !serviceElement ||
          !statusElement ||
          !dateElement ||
          !timeElement ||
          !reasonElement ||
          !notesElement
        ) {

          return;

        }


        const owner =
          ownerElement.value.trim();

        const pet =
          petElement.value.trim();

        const service =
          serviceElement.value;

        const status =
          statusElement.value;

        const date =
          dateElement.value;

        const time =
          timeElement.value;

        const reason =
          reasonElement.value.trim();

        const notes =
          notesElement.value.trim();


        if (
          !owner ||
          !pet ||
          !service ||
          !status ||
          !date ||
          !time ||
          !reason
        ) {

          alert(
            "Please complete all required information."
          );

          return;

        }


        const confirmed =
          confirm(
            "Are you sure you want to save these changes?"
          );


        if (!confirmed) {

          return;

        }


        const appointmentData = {

          appointmentId:
            "APP-001",

          owner:
            owner,

          pet:
            pet,

          service:
            service,

          status:
            status,

          date:
            date,

          time:
            time,

          reason:
            reason,

          notes:
            notes

        };


        localStorage.setItem(
          "editedAppointment",
          JSON.stringify(
            appointmentData
          )
        );


        alert(
          "Appointment updated successfully."
        );


        window.location.href =
          "admindashboard.html";

      }
    );

  }

});

document.addEventListener("DOMContentLoaded", function () {

  const superAdminLoginForm =
    document.getElementById("superAdminLoginForm");

  if (superAdminLoginForm) {

    superAdminLoginForm.addEventListener(
      "submit",
      function (event) {

        event.preventDefault();

        const email =
          document.getElementById(
            "superAdminEmail"
          ).value.trim();

        const password =
          document.getElementById(
            "superAdminPassword"
          ).value;

        if (
          email === "superadmin@fmhanimalclinic.com" &&
          password === "superadmin123"
        ) {

          localStorage.setItem(
            "userRole",
            "superadmin"
          );

          localStorage.setItem(
            "userEmail",
            email
          );

          window.location.href =
            "superadmindashboard.html";

          return;

        }

        alert(
          "Invalid Super Admin email or password."
        );

      }
    );

  }


  const superAdminLogoutBtn =
    document.getElementById(
      "superAdminLogoutBtn"
    );

  if (superAdminLogoutBtn) {

    superAdminLogoutBtn.addEventListener(
      "click",
      function () {

        localStorage.removeItem(
          "userRole"
        );

        localStorage.removeItem(
          "userEmail"
        );

        window.location.href =
          "superadminlogin.html";

      }
    );

  }


  const generateReportBtn =
    document.getElementById(
      "generateReportBtn"
    );

  if (generateReportBtn) {

    generateReportBtn.addEventListener(
      "click",
      function () {

        const reportType =
          document.getElementById(
            "reportType"
          ).value;

        const dateFrom =
          document.getElementById(
            "reportDateFrom"
          ).value;

        const dateTo =
          document.getElementById(
            "reportDateTo"
          ).value;

        const reportResult =
          document.getElementById(
            "reportResult"
          );

        let reportName =
          "Report";

        if (
          reportType ===
          "appointments"
        ) {

          reportName =
            "Appointment Report";

        }

        if (
          reportType ===
          "inventory"
        ) {

          reportName =
            "Inventory Report";

        }

        if (
          reportType ===
          "flow"
        ) {

          reportName =
            "Patient/Customer Flow Report";

        }

        if (
          reportType ===
          "pets"
        ) {

          reportName =
            "Pet Record Summary";

        }

        if (
          reportType ===
          "transactions"
        ) {

          reportName =
            "Transaction Report";

        }

        if (
          reportType ===
          "daily"
        ) {

          reportName =
            "Daily Customer/Patient Count";

        }

        if (reportResult) {

          reportResult.innerHTML =
            reportName +
            " generated successfully." +
            "<br><br>" +
            "From: " +
            (dateFrom || "All dates") +
            "<br>" +
            "To: " +
            (dateTo || "All dates");

        }

      }
    );

  }


  const backupBtn =
    document.getElementById(
      "backupBtn"
    );

  const recoveryBtn =
    document.getElementById(
      "recoveryBtn"
    );

  const backupMessage =
    document.getElementById(
      "backupMessage"
    );

  if (backupBtn) {

    backupBtn.addEventListener(
      "click",
      function () {

        localStorage.setItem(
          "lastBackup",
          new Date().toLocaleString()
        );

        if (backupMessage) {

          backupMessage.textContent =
            "Database backup created successfully.";

        }

      }
    );

  }

  if (recoveryBtn) {

    recoveryBtn.addEventListener(
      "click",
      function () {

        if (backupMessage) {

          const lastBackup =
            localStorage.getItem(
              "lastBackup"
            );

          if (lastBackup) {

            backupMessage.textContent =
              "Recovery point available from " +
              lastBackup +
              ".";

          } else {

            backupMessage.textContent =
              "No database backup is available.";

          }

        }

      }
    );

  }


  const saveSettingsBtn =
    document.getElementById(
      "saveSettingsBtn"
    );

  if (saveSettingsBtn) {

    saveSettingsBtn.addEventListener(
      "click",
      function () {

        const clinicName =
          document.getElementById(
            "clinicName"
          ).value;

        const clinicAddress =
          document.getElementById(
            "clinicAddress"
          ).value;

        const clinicContact =
          document.getElementById(
            "clinicContact"
          ).value;

        const systemStatus =
          document.getElementById(
            "systemStatus"
          ).value;

        localStorage.setItem(
          "clinicName",
          clinicName
        );

        localStorage.setItem(
          "clinicAddress",
          clinicAddress
        );

        localStorage.setItem(
          "clinicContact",
          clinicContact
        );

        localStorage.setItem(
          "systemStatus",
          systemStatus
        );

        const settingsMessage =
          document.getElementById(
            "settingsMessage"
          );

        if (settingsMessage) {

          settingsMessage.textContent =
            "System settings saved successfully.";

        }

      }
    );

  }

});

if (accountContinueBtn) {

  accountContinueBtn.addEventListener(
    "click",
    function () {

      if (!selectedAccountType) {

        return;

      }

      if (
        selectedAccountType === "owner"
      ) {

        window.location.href =
          "login.html?role=owner";

      }

      if (
        selectedAccountType === "assistant"
      ) {

        window.location.href =
          "login.html?role=assistant";

      }

      if (
        selectedAccountType === "admin"
      ) {

        window.location.href =
          "adminlogin.html";

      }

      if (
        selectedAccountType === "superadmin"
      ) {

        window.location.href =
          "superadminlogin.html";

      }

    }
  );

}


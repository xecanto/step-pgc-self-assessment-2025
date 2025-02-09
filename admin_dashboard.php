<?php
require_once 'admin_auth.php';
require_once 'db_connect.php';

// Fetch all applications with user details
$sql = "SELECT a.*, u.cnic 
        FROM applications a 
        JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);

// Format data specifically for Grid.js
$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = [
        'id' => $row['id'],
        'full_name' => $row['full_name'],
        'cnic' => $row['cnic'],
        'program' => $row['program'],
        'status' => ucfirst($row['status']),
        'created_at' => date('d M Y', strtotime($row['created_at']))
    ];
}
?>

<!DOCTYPE html>
<html lang="en" x-data="adminDashboard()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - STEP PGC</title>
    <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.2s ease-in-out;
        }
        .modal-content {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-10%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .gridjs-wrapper {
            border-radius: 0.5rem;
            border: none !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .gridjs-table {
            border: none !important;
        }
        .gridjs-tbody td {
            border-color: #f3f4f6 !important;
        }
        .gridjs-header {
            border: none !important;
            background-color: #f9fafb !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-blue-800 text-white px-6 py-4">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold">STEP PGC Admin</h1>
            <a href="logout.php" class="hover:text-gray-300">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="p-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Applications</h2>
            </div>
            <div id="grid"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="fixed inset-0 z-50 overflow-y-auto" x-show="showEditModal" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="modal-backdrop fixed inset-0" @click="showEditModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="modal-content relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Edit Application</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="saveChanges" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="currentApp.status" class="w-full p-2 border rounded-lg">
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="rejected">Rejected</option>
                            <option value="accepted">Accepted</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                        <select x-model="currentApp.program" class="w-full p-2 border rounded-lg">
                            <option value="MDCAT">MDCAT</option>
                            <option value="ECAT">ECAT</option>
                            <option value="ECAT-CS">ECAT-CS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" x-model="currentApp.full_name" class="w-full p-2 border rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Father's Name</label>
                        <input type="text" x-model="currentApp.father_name" class="w-full p-2 border rounded-lg">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mobile</label>
                            <input type="text" x-model="currentApp.mobile" class="w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" x-model="currentApp.email" class="w-full p-2 border rounded-lg">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="showEditModal = false" 
                                class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function adminDashboard() {
    return {
        showEditModal: false,
        currentApp: {
            id: null,
            full_name: '',
            father_name: '',
            mobile: '',
            email: '',
            program: '',
            status: ''
        },
        async editApplication(id) {
            console.log('Editing application:', id); // Debugging log
            const response = await fetch(`get_application.php?id=${id}`);
            if (response.ok) {
                this.currentApp = await response.json();
                this.showEditModal = true; // Open modal
            }
        },
        async saveChanges() {
            const response = await fetch('update_application.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.currentApp)
            });
            if (await response.ok) {
                this.showEditModal = false;
                window.location.reload();
            }
        }
    };
}

        // Ensure Alpine.js is initialized
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminDashboard', adminDashboard);
        });

        // Expose function globally so Grid.js can access it
        window.editApplication = (id) => {
    document.addEventListener("DOMContentLoaded", () => {
        const element = document.querySelector('[x-data]');
        if (element && element.__x) {
            element.__x.$data.editApplication(id);
        } else {
            console.error('Alpine.js data not found or not initialized yet.');
        }
    });
};

document.addEventListener('alpine:init', () => {
        Alpine.data('adminDashboard', adminDashboard);
    });
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });

        // Grid.js initialization
        const applications = <?php echo json_encode($applications); ?>;
        console.log('Applications data:', applications); // Debug log

        new gridjs.Grid({
            columns: [
                { 
                    id: 'id',
                    name: 'ID',
                    hidden: true
                },
                {
                    id: 'full_name',
                    name: 'Name'
                },
                {
                    id: 'cnic',
                    name: 'CNIC'
                },
                {
                    id: 'program',
                    name: 'Program'
                },
                {
                    id: 'status',
                    name: 'Status',
                    formatter: (cell) => {
                        const statusColors = {
                            'Draft': 'bg-gray-100 text-gray-800',
                            'Submitted': 'bg-blue-100 text-blue-800',
                            'Rejected': 'bg-red-100 text-red-800',
                            'Accepted': 'bg-green-100 text-green-800'
                        };
                        const colorClass = statusColors[cell] || 'bg-gray-100 text-gray-800';
                        return gridjs.html(`<span class="px-2 py-1 rounded-full text-xs ${colorClass}">${cell}</span>`);
                    }
                },
                {
                    id: 'created_at',
                    name: 'Date'
                },
                {
    name: 'Actions',
    formatter: (_, row) => {
        const id = row.cells[0].data; // Get ID from the first column
        return gridjs.html(`
            <div class="flex space-x-3">
                <button @click="editApplication(${id})"
                        class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteApplication(${id})"
                        class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `);
    }
}
            ],
            data: applications.length ? applications : [],
            search: true,
            sort: true,
            pagination: {
                limit: 10
            },
            className: {
                table: 'w-full',
                thead: 'bg-gray-50',
                th: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider',
                td: 'px-6 py-4 whitespace-nowrap text-sm text-gray-900'
            }
        }).render(document.getElementById("grid"));

        // Global function to handle delete clicks from Grid.js
        async function deleteApplication(id) {
            if (confirm('Are you sure you want to delete this application?')) {
                const response = await fetch('delete_application.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                if (await response.ok) {
                    window.location.reload();
                }
            }
        }
    </script>
</body>
</html>

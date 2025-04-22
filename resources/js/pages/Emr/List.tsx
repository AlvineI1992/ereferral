import React, { useState, useEffect } from "react";
import { Pencil, Trash2 } from "lucide-react";
import DataTable from "react-data-table-component";
import axios from "axios";
import { Progress } from "@/components/ui/progress";
import Swal from "sweetalert2";

const Lists = ({ refreshKey, onEdit }) => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const [selectedRoleId, setSelectedId] = useState(null);

  // Fetch data from API
  const fetchData = async (pageNumber = 1, search = "") => {
    setLoading(true);
    try {
      const response = await axios.get(`/api/users?page=${pageNumber}&search=${search}`);
      setData(response.data.data);
      setTotalRows(response.data.total);
    } catch (error) {
      console.error("Error fetching data:", error);
    }
    setLoading(false);
  };

  // Fetch data when refreshKey, page or searchTerm changes
  useEffect(() => {
    fetchData(page, searchTerm);
  }, [refreshKey, page, searchTerm]);

  // Handle Delete action
  const handleDelete = async (id) => {
    const result = await Swal.fire({
      title: "Are you sure?",
      text: "This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, delete it!",
    });

    if (result.isConfirmed) {
      try {
        await axios.delete(`/roles/delete/${id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
          }
        });
        fetchData(page, searchTerm);
        Swal.fire({
          title: "Deleted!",
          text: "The role has been deleted.",
          icon: "success",
          timer: 1500,
          showConfirmButton: false,
        });
      } catch (error) {
        console.error("Error deleting role:", error);
        Swal.fire("Oops!", "Something went wrong.", "error");
      }
    }
  };

  // Handle Edit action
  const handleEdit = (id) => {
    setSelectedId(id);
    onEdit(id); // Pass the selected user to the parent component
  };

  const columns = [
    { name: "ID", selector: (row) => row.id, sortable: true },
    { name: "Name", selector: (row) => row.name, sortable: true },
    { name: "Email", selector: (row) => row.email },
    { name: "Status", selector: (row) => row.status },
    {
      name: "Actions",
      cell: (row) => (
        <div className="flex gap-1">
          <button onClick={() => handleEdit(row)} className="p-1 text-blue-500 hover:text-blue-700">
            <Pencil size={16} />
          </button>
          <button onClick={() => handleDelete(row.id)} className="p-1 text-red-500 hover:text-red-700">
            <Trash2 size={16} />
          </button>
        </div>
      ),
      ignoreRowClick: true,
    },
  ];

  return (
    <div className="p-3 bg-white rounded-lg shadow-md mr-3 ml-3 mt-3">
      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center space-x-2">
          <List size="16" />
          <h2 className="text-lg font-semibold">Users</h2>
        </div>
        <input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="px-2 py-1 border rounded-md text-sm w-56"
        />
      </div>
      {loading ? (
        <div className="flex justify-center items-center py-4">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="text-sm text-blue-600">&nbsp;Please wait...</span>
        </div>
      ) : (
        <DataTable
          columns={columns}
          data={data}
          pagination
          paginationServer
          paginationTotalRows={totalRows}
          onChangePage={(page) => setPage(page)}
          striped
          highlightOnHover
          className="text-sm"
          customStyles={{
            rows: { style: { cursor: "pointer" } },
            headCells: { style: { borderBottom: "1px solid #ddd" } },
            cells: { style: { borderBottom: "1px solid #ddd" } },
          }}
        />
      )}
    </div>
  );
};

export default Lists;

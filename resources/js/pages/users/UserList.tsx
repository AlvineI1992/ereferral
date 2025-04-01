import React, { useState, useEffect } from "react"; 
import { Pencil, Trash2 } from "lucide-react";
import DataTable from "react-data-table-component";
import axios from "axios";
import { Progress } from "@/components/ui/progress";


const UserList = () => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);

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

  useEffect(() => {
    fetchData(page, searchTerm);
  }, [page, searchTerm]);

  const handleEdit = (id) => {
    console.log("Edit user with ID:", id);
  };

  const handleDelete = async (id) => {
    if (window.confirm("Are you sure you want to delete this user?")) {
      try {
        await axios.delete(`/api/users/${id}`);
        fetchData(page, searchTerm);
      } catch (error) {
        console.error("Error deleting user:", error);
      }
    }
  };

  // Columns
  const columns = [
    { name: "ID", selector: (row) => row.id, sortable: true },
    { name: "Name", selector: (row) => row.name, sortable: true },
    { name: "Email", selector: (row) => row.email },
    { name: "Status", 
      selector: (row) => row.status === "A" ? "Active" : row.status === "I" ? "Inactive" : "N/A" 

    },
    {
      name: "Actions",
      cell: (row) => (
        <div style={{ display: "flex", gap: "5px" }}>
          <button 
            onClick={() => handleEdit(row.id)}
            style={{
              width: "auto", 
              padding: "2px 4px", 
              border: "1px", 
              background: "transparent",
              cursor: "pointer"
            }}
          >
            <Pencil size={16} />
          </button>
          <button 
            onClick={() => handleDelete(row.id)}
            style={{
              width: "auto", 
              padding: "2px 4px", 
              border: "1px", 
              background: "transparent",
              cursor: "pointer"
            }}
          >
            <Trash2 size={16} />
          </button>
        </div>
      ),
      ignoreRowClick: true,
    },
  ];

  return (
    <div>
       <div className="flex justify-between items-center mb-4">
        <h2 className="text-lg font-semibold">User Lists</h2>
        <input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="px-3 py-2 border rounded-sm w-60"
        />
      </div>

{loading ? (
        <div className="flex justify-center items-center py-4">
          <Progress className="w-6 h-6 animate-spin text-blue-500" />
        </div>
      ) : (
        <DataTable
          columns={columns}
          data={data}
          pagination
          paginationServer
          paginationTotalRows={totalRows}
          onChangePage={(page) => setPage(page)}
       
        />
      )}
    </div>
  );
};

export default UserList;

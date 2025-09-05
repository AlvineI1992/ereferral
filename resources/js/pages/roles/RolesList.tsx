import React, { useState, useEffect } from "react";
import { Pencil, Trash2, List, CircleArrowRight, ChevronLeft, ChevronRight } from "lucide-react";
import axios from "axios";
import Swal from "sweetalert2";
import { Inertia } from "@inertiajs/inertia";
type RolesListProps = {
  refreshKey: any; 
  onEdit: (id: number) => void;
  canEdit: boolean; 
  canDelete: boolean; 
  canAssign: boolean; 
};
const RolesList = ({ canEdit,canDelete,canAssign,refreshKey, onEdit }: RolesListProps) => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const perPage = 10; 

  const fetchData = async (pageNumber = 1, search = "", id = null) => { 
    setLoading(true);
    try {

      const baseUrl = id ? `/roles/${id}` : `/roles/list`;


      const response = await axios.get(`${baseUrl}?page=${pageNumber}&search=${search}`);
      
      setData(response.data.data);
      setTotalRows(response.data.total);
    } catch (error) {
      console.error("Error fetching data:", error);
    }
    setLoading(false);
 };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchData(page, searchTerm);
    }, 500);

    return () => clearTimeout(delayDebounce);
  }, [refreshKey, page, searchTerm]);

  const handleGoto = (id) => {
    if (!id) {
      console.error("ID parameter is required");
      return;
    }
    Inertia.visit(`/roles/assign/${id}`);
  };

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
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          },
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

  const handleEdit = (row) => {
    onEdit?.(row);

  };

  const handleView = (row) => {
    console.log("View details of:", row);
    // You can replace this with your view logic (maybe open modal or redirect)
  };

  const totalPages = Math.ceil(totalRows / perPage);

  const handlePreviousPage = () => {
    if (page > 1) setPage(page - 1);
  };

  const handleNextPage = () => {
    if (page < totalPages) setPage(page + 1);
  };

  const startEntry = (page - 1) * perPage + 1;
  const endEntry = Math.min(startEntry + perPage - 1, totalRows);

  return (
    <div className="mr-3 ml-3 mt-3">
      {/* Header */}
      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center space-x-2">
          <List size={16} />
          <h2 className="text-lg font-semibold">Roles</h2>
        </div>
        <input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="px-2 py-1 border rounded-md text-sm w-56"
        />
      </div>

      {/* Table with loading */}
      {loading ? (
        <div className="flex justify-center items-center py-4">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="text-sm text-blue-600">&nbsp;Please wait...</span>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm text-left border-collapse">
            <thead >
              <tr>
                <th className="px-4 py-2 border-b">ID</th>
                <th className="px-4 py-2 border-b">Name</th>
                <th className="px-4 py-2 border-b">Guard</th>
                <th className="px-4 py-2 border-b text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.length > 0 ? (
                data.map((row) => (
                  <tr key={row.id} className="hover:bg-gray-50">
                    <td className="px-4 py-2 border-b">{row.id}</td>
                    <td className="px-4 py-2 border-b">{row.name}</td>
                    <td className="px-4 py-2 border-b">{row.guard_name}</td>
                    <td className="px-4 py-2 border-b text-center">
                      <div className="flex justify-center gap-2">
                      {canEdit && (
                        <button
                          onClick={() => handleEdit(row.id)}
                          className="p-1 text-blue-500 hover:text-blue-700"
                        >
                          <Pencil size={16} />
                        </button>)}
                        {canDelete && (
                        <button
                          onClick={() => handleDelete(row.id)}
                          className="p-1 text-red-500 hover:text-red-700"
                        >
                          <Trash2 size={16} />
                        </button>)}
                        {canAssign && (
                        <button
                          onClick={() => handleGoto(row.id)}
                          className="p-1 text-green-500 hover:text-green-700"
                        >
                          <CircleArrowRight size={16} />
                        </button>)}
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colspan="4" className="text-center py-4">
                    No roles found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          {/* Info and Pagination */}
          <div className="flex justify-between items-center mt-4 px-2">
            {/* Info */}
            <div className="text-sm text-gray-600">
              {totalRows > 0 ? (
                <>Showing {startEntry} to {endEntry} of {totalRows} entries</>
              ) : (
                <>No entries to show</>
              )}
            </div>

            {/* Pagination */}
            <div className="flex items-center gap-2">
              <button
                onClick={handlePreviousPage}
                disabled={page === 1}
                className={`flex items-center gap-1 px-3 py-1 border rounded-md text-sm ${
                  page === 1 ? "text-gray-400 border-gray-300" : "text-blue-600 border-blue-300 hover:bg-blue-50"
                }`}
              >
                <ChevronLeft size={16} />
                Previous
              </button>
              <span className="text-sm text-gray-600">
                Page {page} of {totalPages}
              </span>
              <button
                onClick={handleNextPage}
                disabled={page === totalPages}
                className={`flex items-center gap-1 px-3 py-1 border rounded-md text-sm ${
                  page === totalPages ? "text-gray-400 border-gray-300" : "text-blue-600 border-blue-300 hover:bg-blue-50"
                }`}
              >
                Next
                <ChevronRight size={16} />
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default RolesList;

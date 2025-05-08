import React, { useState, useEffect } from "react";
import { Pencil, Trash2, List } from "lucide-react";
import axios from "axios";
import Swal from "sweetalert2";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

type Props = {
  refreshKey: () => any;
  onEdit: () => any;  // Add onCancel prop
  canDelete:boolean;// Role data for editing
  canEdit:boolean;// Role data for editing
};


const Lists = ({ canEdit,canDelete,refreshKey, onEdit }:Props) => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const perPage = 10;

  const fetchData = async (pageNumber = 1, search = "") => {
    setLoading(true);
    try {
      const response = await axios.get(`/api/permission?page=${pageNumber}&search=${search}`);
      setData(response.data.data);
      setTotalRows(response.data.total);
    } catch (error) {
      console.error("Error fetching roles:", error);
    }
    setLoading(false);
  };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchData(page, searchTerm);
    }, 500); // 0.5-second debounce

    return () => clearTimeout(delayDebounce);
  }, [refreshKey, page, searchTerm]);

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
        await axios.delete(`/permission/delete/${id}`, {
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

  const handleEdit = (row:any) => {
    onEdit?.(row);
  };

  const totalPages = Math.ceil(totalRows / perPage);

  return (
    <div className="mr-3 ml-3 mt-3">
      {/* Header */}
      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center space-x-2">
          <List size={16} />
          <h2 className="text-lg font-semibold">Permissions</h2>
        </div>
        <Input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="px-2 py-1 border rounded-md text-sm w-56"
        />
      </div>

      {/* Table */}
      {loading ? (
        <div className="flex justify-center items-center py-4">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="text-sm text-blue-600">&nbsp;Please wait...</span>
        </div>
      ) : (
        <>
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead >
              <tr>
                <th className="px-2 py-1 text-left font-medium">ID</th>
                <th className="px-2 py-1 text-left font-medium">Name</th>
                <th className="px-2 py-1 text-left font-medium">Guard</th>
                <th className="px-2 py-1 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data.length > 0 ? (
                data.map((row) => (
                  <tr key={row.id}>
                    <td className="px-2 py-1">{row.id}</td>
                    <td className="px-2 py-1">{row.name}</td>
                    <td className="px-2 py-1">{row.guard_name}</td>
                    <td className="px-2 py-1 text-right">
                      <div className="flex justify-end space-x-1">
                      {canEdit && (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleEdit(row)}
                          className="text-blue-500 hover:text-blue-700"
                        >
                              <Pencil size={16} />
                           </Button>
                        )}
      
      {canDelete && (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(row.id)}
                          className="text-red-500 hover:text-red-700"
                        >
                          <Trash2 size={16} />
                        </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="4" className="text-center text-gray-500 italic py-4">
                    No roles found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          {/* Pagination */}
          <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-4 px-2 space-y-2 sm:space-y-0">
            <div className="text-xs text-gray-600">
              Page <span className="font-medium">{page}</span> of{" "}
              <span className="font-medium">{totalPages}</span> &nbsp;(
              {totalRows} {totalRows === 1 ? "record" : "records"})
            </div>

            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(p - 1, 1))}
                className="text-xs p-1 cursor-pointer"
              >
                Previous
              </Button>

              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .slice(Math.max(0, page - 3), Math.min(totalPages, page + 2))
                .map((pNum) => (
                  <Button
                    key={pNum}
                    variant={pNum === page ? "default" : "outline"}
                    className="px-3 py-1 text-xs cursor-pointer"
                    onClick={() => setPage(pNum)}
                  >
                    {pNum}
                  </Button>
                ))}

              <Button
                variant="outline"
                disabled={page >= totalPages}
                onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
                className="text-xs p-1 cursor-pointer"
              >
                Next
              </Button>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default Lists;

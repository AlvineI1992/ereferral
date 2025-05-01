import React, { useState, useEffect } from "react";
import { List, ChevronLeft, ChevronRight, Save } from "lucide-react";
import axios from "axios";
import Swal from "sweetalert2";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";

type Role = {
  id: number;
  name: string;
  guard_name: string;
};

type ListProps = {
  refreshKey: any;
  id: number | null;
  is_include: boolean | null;
  onSave: () => void;
};

const RolesListAssign = ({ onSave, refreshKey, id: selectedRoleId, is_include }: ListProps) => {
  const [data, setData] = useState<Role[]>([]);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [selectAll, setSelectAll] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const perPage = 10;

  const fetchData = async (pageNumber = 1, search = "") => {
    if (!selectedRoleId) {
      setData([]);
      setTotalRows(0);
      return;
    }

    setLoading(true);
    try {
      const response = await axios.get(
        `/permission-has-role?page=${pageNumber}&search=${search}&role_id=${selectedRoleId}&is_include=${is_include}`
      );
      setData(response.data.data);
      setTotalRows(response.data.total);
    } catch (error) {
      console.error("Error fetching data:", error);
      Swal.fire("Error", "Failed to load permissions.", "error");
    }
    setLoading(false);
  };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchData(page, searchTerm);
      setSelectedIds([]);
      setSelectAll(false);
    }, 500);

    return () => clearTimeout(delayDebounce);
  }, [refreshKey, page, searchTerm]);

  const handleSelectOne = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedIds((prev) => [...prev, id]);
    } else {
      setSelectedIds((prev) => prev.filter((item) => item !== id));
    }
  };

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      const allIds = data.map((role) => role.id);
      setSelectedIds(allIds);
    } else {
      setSelectedIds([]);
    }
    setSelectAll(checked);
  };
  const handleSubmit = async () => {
    if (!selectedRoleId) {
      Swal.fire("No Role Selected", "Please select a role first.", "warning");
      return;
    }
  
    if (selectedIds.length === 0) {
      Swal.fire("No Selection", "Please select at least one permission.", "warning");
      return;
    }
  
    setProcessing(true);
    try {
      if (is_include) {
        await axios.patch(
          `/api/assign-permissions/${selectedRoleId}`,
          { permissionids: selectedIds },
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
          }
        );
        Swal.fire("Success", "Permissions assigned successfully.", "success");
      } else {
        
        await axios.patch(
          `/api/revoke-permissions/${selectedRoleId}`,
          { permissionsids: selectedIds },
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
          }
        );
        Swal.fire("Success", "Permissions revoked successfully.", "success");
      }
  
      // Reset selection
      setSelectedIds([]);
      setSelectAll(false);
      onSave();
    } catch (error) {
      console.error(error);
      Swal.fire("Error", "Something went wrong while processing permissions.", "error");
    } finally {
      setProcessing(false);
    }
  };

  const totalPages = Math.ceil(totalRows / perPage);
  const startEntry = (page - 1) * perPage + 1;
  const endEntry = Math.min(startEntry + perPage - 1, totalRows);

  return (
    <div className="p-3 mr-3 ml-3 mt-3">
      {/* Header */}
      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center space-x-2">
          
          <h2
            className={`text-lg font-semibold ${is_include ? 'text-blue-600' : 'text-green-600'
              }`}
          >
            {is_include ? "Available Permission" : "Access Permission"}
          </h2>
        </div>

        {/* Save Button */}
        <div className="flex justify-end mb-3">
          <Button
            onClick={handleSubmit}
            disabled={processing}
            className="flex gap-1 border border-green-700 bg-white text-green-700 hover:bg-green-600 hover:text-white font-semibold py-2 px-3 rounded-sm transition-all"
          >
            {processing ? (
              <span className="animate-pulse">Processing...</span>
            ) : (
              <>
                <Save size={16} />
                Save
              </>
            )}
          </Button>
        </div>
      </div>

      {/* Search */}
      <div className="flex justify-end gap-2 mb-2">
        <input
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
        <div className="overflow-x-auto">
          <table className="min-w-full text-sm text-left border-collapse">
            <thead className="bg-gray-100">
              <tr>
                <th className="px-4 py-2 border-b">
                  <Checkbox
                    checked={selectAll}
                    onCheckedChange={handleSelectAll}
                  />
                </th>
                <th className="px-4 py-2 border-b">ID</th>
                <th className="px-4 py-2 border-b">Name</th>
                <th className="px-4 py-2 border-b">Guard</th>
              </tr>
            </thead>
            <tbody>
              {data.length > 0 ? (
                data.map((row) => (
                  <tr key={row.id} className="hover:bg-gray-50">
                    <td className="px-4 py-2 border-b">
                      <Checkbox
                        checked={selectedIds.includes(row.id)}
                        onCheckedChange={(checked) => handleSelectOne(row.id, checked)}
                      />
                    </td>
                    <td className="px-4 py-2 border-b">{row.id}</td>
                    <td className="px-4 py-2 border-b">{row.name}</td>
                    <td className="px-4 py-2 border-b">{row.guard_name}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="text-center py-4">
                    No permission found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          {/* Info and Pagination */}
          <div className="flex flex-col md:flex-row justify-between items-center mt-4 px-2 gap-2">
            <div className="text-sm text-gray-600">
              {totalRows > 0 ? (
                <>Showing {startEntry} to {endEntry} of {totalRows} entries</>
              ) : (
                <>No entries to show</>
              )}
            </div>

            <div className="flex items-center gap-2">
              {/* Previous button */}
              <button
                onClick={() => setPage((prev) => Math.max(prev - 1, 1))}
                disabled={page === 1}
                className={`flex items-center gap-1 px-3 py-1 border rounded-md text-sm ${page === 1 ? "text-gray-400 border-gray-300" : "text-blue-600 border-blue-300 hover:bg-blue-50"}`}
              >
                <ChevronLeft size={16} />
                Previous
              </button>

              {/* Page Numbers */}
              <div className="flex gap-1">
                {Array.from({ length: totalPages }, (_, index) => index + 1).map((num) => (
                  <button
                    key={num}
                    onClick={() => setPage(num)}
                    className={`px-3 py-1 border rounded-md text-sm ${num === page ? "bg-blue-600 text-white" : "text-blue-600 border-blue-300 hover:bg-blue-50"}`}
                  >
                    {num}
                  </button>
                ))}
              </div>

              {/* Next button */}
              <button
                onClick={() => setPage((prev) => Math.min(prev + 1, totalPages))}
                disabled={page === totalPages}
                className={`flex items-center gap-1 px-3 py-1 border rounded-md text-sm ${page === totalPages ? "text-gray-400 border-gray-300" : "text-blue-600 border-blue-300 hover:bg-blue-50"}`}
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

export default RolesListAssign;

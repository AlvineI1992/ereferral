import React, { useState, useEffect } from "react";
import { Pencil, Trash2, List, Hospital, Mars, Venus, Printer, ArrowRightIcon } from "lucide-react";
import axios from "axios";
import Swal from "sweetalert2";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { PermissionProps } from './types';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';

const Lists = ({ canEdit, canDelete, refreshKey, onEdit }: PermissionProps) => {
  const [data, setData] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalRows, setTotalRows] = useState(0);
  const [perPage, setPerPage] = useState(10);

  const fetchData = async (pageNumber = 1, search = "") => {
    setLoading(true);
    try {
      const response = await axios.get(`/incoming/list?page=${pageNumber}&search=${search}&per_page=${perPage}`);
      setData(response.data.data);
      setTotalRows(response.data.total);
    } catch (error) {
      console.error("Error fetching referrals:", error);
    }
    setLoading(false);
  };

  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchData(page, searchTerm);
    }, 500);
    return () => clearTimeout(delayDebounce);
  }, [refreshKey, page, searchTerm, perPage]);

  const handleDelete = async (id: number) => {
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
          text: "The record has been deleted.",
          icon: "success",
          timer: 1500,
          showConfirmButton: false,
        });
      } catch (error) {
        console.error("Error deleting record:", error);
        Swal.fire("Oops!", "Something went wrong.", "error");
      }
    }
  };

  const handleEdit = (row: any) => {
    onEdit?.(row);
  };

  const totalPages = Math.ceil(totalRows / perPage);

  return (
    <div className="p-4 w-full h-full overflow-x-auto">
      {/* Header */}
      <div className="flex items-center space-x-2 mb-3">
        <List size={20} />
        <h2 className="text-xl font-semibold">Incoming Referrals</h2>
      </div>
      <div className="flex justify-between items-center mb-3">
        <div className="flex items-center gap-1">
          <label htmlFor="perPage">Rows per page:</label>
          <select
            id="perPage"
            value={perPage}
            onChange={(e) => {
              setPage(1); // reset to first page
              setPerPage(Number(e.target.value));
            }}
            className="border rounded px-2 py-1 text-xs"
          >
            <option value={5}>5</option>
            <option value={10}>10</option>
            <option value={25}>25</option>
            <option value={50}>50</option>
          </select>
        </div>


        <Input
          type="text"
          placeholder="Search..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-64 text-sm"
        />
      </div>

      {/* Table */}
      {loading ? (
        <div className="flex justify-center items-center py-8">
          <div className="w-6 h-6 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          <span className="ml-2 text-sm text-blue-600">Loading referrals...</span>
        </div>
      ) : (
        <>
          <table className="min-w-full text-sm">
            <thead>
              <tr>
                <th className="px-1 py-2 text-left">LogID</th>
                <th className="px-1 py-2 text-left">Referral Date</th>
                <th className="px-1 py-2 text-left">Patient Name</th>

                <th className="px-1 py-2 text-left">Origin</th>
                <th className="px-1 py-2 text-left">Destination</th>
                <th className="px-1 py-2 text-left">Type</th>
                <th className="px-1 py-2 text-left">Category</th>
                <th className="px-1 py-2 text-left">Reason</th>
                <th className="px-1 py-2 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {data.length > 0 ? (
                data.map((row: any) => (
                  <tr key={row.LogID} className="border-t">
                    <td className="px-1 py-2">{row.LogID}</td>
                    <td className="px-1 py-2">{row.referral_date} {row.referral_time}</td>
                    <td className="px-1 py-2 align-top">
                      <div className="flex flex-col items-start gap-1">
                        {/* Avatar and Name */}
                        <div className="flex items-center gap-1">
                          <Avatar className="w-8 h-8">
                            <AvatarImage src={row.avatar || '/default-avatar.jpg'} />
                            <AvatarFallback>
                              {row.patient_name?.charAt(0).toUpperCase()}
                              {row.patient_name?.charAt(1).toUpperCase()}
                            </AvatarFallback>
                          </Avatar>
                          <span className="text-sm">{row.patient_name}</span>
                        </div>

                        {/* Sex */}
                        <div className="flex items-center space-x-1 ml-10">
                          {row.patient_sex === 'Male' ? (
                            <Mars
                              className="text-blue-700 group-hover:text-white transition-colors cursor-pointer"
                              size={12}
                            />
                          ) : (
                            <Venus
                              className="text-pink-700 group-hover:text-white transition-colors cursor-pointer"
                              size={12}
                            />
                          )}
                          <span
                            className={
                              row.patient_sex === 'Male'
                                ? 'text-[10px] text-blue-700 group-hover:text-white transition-colors cursor-pointer'
                                : 'text-[10px] text-pink-700 group-hover:text-white transition-colors cursor-pointer'
                            }
                          >
                            {row.patient_sex}
                          </span>
                        </div>
                        <div className="flex items-center space-x-1 ml-10">
                        <span className="text-[10px]">{row.patient_birthdate}</span>
                          </div>
                      </div>
                    </td>

                    <td className="px-1 py-2">
                      <div className="flex items-center space-x-1">
                        <Hospital size={12} />

                        <span className="text-[10px]">{row.referral_origin_name}</span>
                      </div>
                    </td>
                    <td className="px-1 py-2">
                      <div className="flex items-center space-x-1">
                        <Hospital size={12} />
                        <span className="text-[10px]">{row.referral_destination_name}</span>
                      </div>
                    </td>
                    <td className="px-1 py-2">
                      <div className="flex items-center space-x-1">
                        <span className="text-[10px]">{row.referral_type_description}</span>
                      </div>
                    </td>
                    <td className="px-1 py-2">{row.referral_category}</td>
                    <td className="px-1 py-2"><span className="text-[10px]">{row.referral_reason_description}</span></td>
                    <td className="px-1 py-2">
                      <div className="flex gap-1">
                        <Button
                          variant="outline"
                          size="icon"
                          onClick={() => handleEdit(row)}
                          className="group hover:bg-green-500 px-2 py-1"
                        >
                          <Printer
                            size={16}
                            className="text-blue-700 group-hover:text-white transition-colors"
                          />
                        </Button>

                        <Button
                          variant="outline"
                          size="icon"
                          onClick={() => handleDelete(row.LogID)}
                          className="group hover:bg-green-300"
                        >
                          <Trash2 size={16} className="text-red-700 group-hover:text-white transition-colors" />
                        </Button>
                        <Button
                          variant="outline"
                          size="icon"
                          onClick={() => handleEdit(row)}
                          className="group hover:bg-green-300"
                        >
                          <ArrowRightIcon size={16} className="text-blue-300 group-hover:text-white transition-colors" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={8} className="text-center text-gray-500 italic py-6">
                    No referrals found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>

          {/* Pagination Controls */}
          <div className="flex justify-between items-center mt-4 text-xs text-gray-600">
            <div className="flex items-center space-x-4">
              <span>
                Page <strong>{page}</strong> of <strong>{totalPages}</strong> ({totalRows} total)
              </span>

            </div>
            <div className="flex gap-2">
              <Button
                variant="outline"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(p - 1, 1))}
                className="text-xs px-2 py-1"
              >
                Previous
              </Button>

              {Array.from({ length: totalPages }, (_, i) => i + 1)
                .slice(Math.max(0, page - 3), Math.min(totalPages, page + 2))
                .map((pNum) => (
                  <Button
                    key={pNum}
                    variant={pNum === page ? "default" : "outline"}
                    className="text-xs px-3 py-1"
                    onClick={() => setPage(pNum)}
                  >
                    {pNum}
                  </Button>
                ))}

              <Button
                variant="outline"
                disabled={page >= totalPages}
                onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
                className="text-xs px-2 py-1"
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

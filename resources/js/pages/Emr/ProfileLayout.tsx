import { Button } from '@/components/ui/button';
import Swal from "sweetalert2";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import { useEffect, useState } from 'react';
import Reference_List from '../Ref_Facilities/Reference_List';
import Active_List from '../Ref_Facilities/Active_List';
import Profileinfo from './Profileinfo';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Provider', href: '/emr' },
  { title: 'Profile', href: '' },
];

type ProfileLayoutProps = {
  id: string;
  children?: React.ReactNode; // optional if unused
};

type ProfileType = {
  emr_name: string;
  role: string;
  email: string;
  phone: string;
  location: string;
  avatar?: string;
};

export default function ProfileLayout({ id, children }: ProfileLayoutProps) {
  const [profile, setProfile] = useState<ProfileType | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);
  const [showModal, setShowModal] = useState(false);
  const [selectedFacilities, setSelectedFacilities] = useState<string[]>([]);

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        const response = await axios.get(`/emr/info/${id}`, {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        });
        setProfile(response.data);
      } catch (error) {
        console.error('Failed to fetch profile:', error);
      }
    };

    fetchProfile();
  }, [id]);

  const handleConfirm = async () => {
    try {
      const payload = {
        emr_id: id,
        facilities: selectedFacilities,
      };

      const response = await axios.post(
        '/emr/assign',
        payload
      );
      setRefreshKey(prev => prev + 1);
      Swal.fire("Assigned", `${selectedFacilities.length} facility(ies) assigned.`, "success");
    } catch (error) {
      console.error('Error assigning facilities:', error);
    } finally {
      setShowModal(false);
    }
  };


  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <div className="flex flex-col gap-4 p-4 lg:flex-row">
        <div className="w-full space-y-4 lg:w-1/2">
          {profile && <Profileinfo profile={profile} />}
        </div>
        <div className="flex w-full items-start justify-end lg:w-1/2">
          <Button onClick={() => setShowModal(true)}>Add Facility</Button>
        </div>
      </div>

      {/* Modal Controlled Here */}
      <Dialog open={showModal} onOpenChange={setShowModal}>
        <DialogContent className="w-full max-w-md sm:max-w-xl md:max-w-2xl lg:max-w-7xl">
          <DialogHeader>
            <DialogTitle>Active facilities</DialogTitle>
            <DialogDescription>Select active facilities to assign.</DialogDescription>
          </DialogHeader>

          <div className="mt-4">
            <Active_List
              refreshKey={refreshKey}
              id={id}
              setSelectedFacilities={setSelectedFacilities}
            />
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setShowModal(false)}>
              Cancel
            </Button>
            <Button onClick={handleConfirm}>Confirm</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <div className="my-4 border-t" />

      <div className="flex flex-col">
        <Reference_List refreshKey={refreshKey} id={id} />
      </div>

      {/* Optional children slot rendering */}
      {children}
    </AppLayout>
  );
}

import React, { useState } from "react";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import  Active_List  from "../Ref_Facilities/Active_List";

const Facility_Active_List = () => {
  const [showModal, setShowModal] = useState(false);
  const [refreshKey, setRefreshKey] = useState(Date.now());
  const [selectedFacilities, setSelectedFacilities] = useState<string[]>([]); // To store the selected facility IDs

  // Handler when user clicks "Confirm"
  const handleConfirm = () => {
    // Submit selected facilities (can be sent to an API or used for some other purpose)
    console.log("Selected Facilities: ", selectedFacilities);
    
    // Close the modal
    setShowModal(false);
  };

  return (
    <div>
     

      <Dialog open={showModal} onOpenChange={setShowModal}>
        <DialogContent className="w-full max-w-md sm:max-w-xl md:max-w-2xl lg:max-w-7xl">
          <DialogHeader>
            <DialogTitle>Active facilities</DialogTitle>
            <DialogDescription>Select active facilities to assign.</DialogDescription>
          </DialogHeader>
          <div className="mt-4">
            {/* Pass setSelectedFacilities to Active_List to get the selected facilities */}
            <Active_List refreshKey={refreshKey} id={null} setSelectedFacilities={setSelectedFacilities} />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowModal(false)}>
              Cancel
            </Button>
            {/* The Confirm button triggers the handleConfirm function */}
            <Button onClick={handleConfirm}>Confirm</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default Facility_Active_List;

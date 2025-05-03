import axios from 'axios';

/**
 * Fetch a single permission by ID.
 * @param id - The ID of the permission to retrieve.
 * @returns The permission data from the server.
 */
export const show = async (id: number) => {
    try {
        const response = await axios.get(`/permissions/${id}`);
        return response.data;
    } catch (error) {
        console.error('Error fetching permission:', error);
        throw error;
    }
};

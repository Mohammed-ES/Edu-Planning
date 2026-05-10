function confirmDelete(id, csrf) {
    Swal.fire({
        title: 'Delete Confirmation',
        text: 'Are you sure you want to delete this item? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
        background: '#fff',
        color: '#2C1800',
        customClass: {
            popup: 'rounded-4'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteCsrf').value = csrf;
            document.getElementById('deleteForm').submit();
        }
    })
}
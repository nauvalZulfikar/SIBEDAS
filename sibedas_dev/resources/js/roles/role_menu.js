class RoleMenus {
    init() {
        this.initCheckboxRoles();
    }

    initCheckboxRoles() {
        const childPermissions =
            document.querySelectorAll(".child-permissions");

        childPermissions.forEach((child) => {
            child.addEventListener("change", function () {
                const parentId = this.dataset.parentId;
                const parentShow = document.querySelector(
                    `input[name='permissions[${parentId}][allow_show]']`
                );

                if (parentShow) {
                    // If any child permission is checked, check parent "Show"
                    if (
                        document.querySelectorAll(
                            `.child-permission[data-parent-id="${parentId}"]:checked`
                        ).length > 0
                    ) {
                        parentShow.checked = true;
                    } else {
                        parentShow.checked = false;
                    }
                }
            });
        });
    }
}

document.addEventListener("DOMContentLoaded", function (event) {
    new RoleMenus().init();
});


<div class="row">
    <div class="col-12 mb-3">
        <div class="left-header-content">
            <div class="row">
                <div class="col-2">
                    <label class="label">เลือกจาก</label>
                    <select class="form-control select2" name="filter1" id="filter1">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                    </select>
                </div>
                <div class="col-2">
                    <div class="form-group mb-0">
                        <label class="label">Date Picker</label>
                        <div class="form-group position-relative">
                            <i class="ri-calendar-line position-absolute top-50 translate-middle-y fs-20 text-gray-light ps-20"></i>
                            <input type="text" name="filter2" id="filter2" class="form-control datepicker text-dark ps-5" value="<?php echo date("d/m/Y"); ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-2">
                    <div class="src-form position-relative">
                        <label class="label">Search</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search here.....">
                    </div>
                </div>
                <div class="col-1">
                    <div class="src-form position-relative">
                        <label class="label"> &nbsp;</label>
                        <button type="button" class="btn btn-outline-info fw-medium w-100 hover-white"><span class="material-symbols-outlined">filter_list</span></button>
                    </div>
                </div>
                <div class="col-1">
                    <div class="src-form position-relative">
                        <label class="label"> &nbsp;</label>
                        <button type="button" class="btn btn-outline-success fw-medium w-100 hover-white"><span class="material-symbols-outlined">search</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
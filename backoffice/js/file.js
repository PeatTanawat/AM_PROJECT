class FileUploaderValidator {
    /**
     * @param {Object} options - ตั้งค่าเริ่มต้น
     */
    constructor(options = {}) {
        this.selector = options.selector || '.file-validator';
        this.defaultMaxSize = options.defaultMaxSize || 2; // Default 2MB
        
        // เริ่มต้นดักจับ Event ทันทีที่เรียกใช้ Class
        this.init();
    }

    init() {
        // ใช้ Event Delegation ที่ระดับ document เพื่อรองรับ Input ที่อาจถูก Render มาจาก API ในภายหลัง
        document.addEventListener('change', (event) => {
            const target = event.target;
            
            // ตรวจสอบว่า element ที่เกิด event change คือ input ที่มีคลาสตรงตามที่กำหนดหรือไม่
            if (target.matches(this.selector) && target.type === 'file') {
                this.validate(target);
            }
        });
    }

    validate(inputElement) {
        const file = inputElement.files[0];
        if (!file) return; // กรณีกดยกเลิก

        // ดึงค่า Config จาก Dataset (HTML5 data-* attributes)
        const maxSizeMB = parseFloat(inputElement.dataset.maxSize) || this.defaultMaxSize;
        const allowedTypesRaw = inputElement.dataset.allowedTypes;
        const allowedTypes = allowedTypesRaw ? allowedTypesRaw.split(',').map(t => t.trim().toLowerCase()) : [];
        const allowedTypesMessage = inputElement.dataset.allowedTypesMessage || `ระบบรองรับเฉพาะไฟล์ประเภท:\n${allowedTypes.join(', ')}`;

        // 1. ตรวจสอบขนาดไฟล์
        if (!this.isValidSize(file, maxSizeMB)) {
            this.showError('ขนาดไฟล์เกินกำหนด', `กรุณาอัปโหลดไฟล์ขนาดไม่เกิน ${maxSizeMB} MB`);
            this.resetInput(inputElement);
            return;
        }

        // 2. ตรวจสอบประเภทไฟล์
        if (!this.isValidType(file, allowedTypes)) {
            this.showError('ประเภทไฟล์ไม่ถูกต้อง', allowedTypesMessage);
            this.resetInput(inputElement);
            return;
        }

        // 3. ผ่านเงื่อนไขทั้งหมด -> ส่ง Custom Event ออกไปเพื่อให้ระบบอื่นรับช่วงต่อ
        this.dispatchSuccessEvent(inputElement, file);
    }

    isValidSize(file, maxSizeMB) {
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        return file.size <= maxSizeBytes;
    }

    isValidType(file, allowedTypes) {
        if (allowedTypes.length === 0) return true; // ไม่ได้จำกัดประเภท
        
        const fileType = file.type.toLowerCase();
        
        return allowedTypes.some(type => {
            // รองรับ Wildcard เช่น image/*
            if (type.endsWith('/*')) {
                const baseType = type.split('/')[0];
                return fileType.startsWith(`${baseType}/`);
            }
            // ตรวจสอบแบบเจาะจง (Exact Match)
            return fileType === type;
        });
    }

    showError(title, text) {
        // ตรวจสอบว่ามีการโหลด SweetAlert2 มาแล้วหรือไม่ เพื่อป้องกัน Script Error
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: title,
                text: text,
                confirmButtonColor: '#3085d6'
            });
        } else {
            alert(`${title}\n${text}`);
        }
    }

    resetInput(inputElement) {
        inputElement.value = '';
    }

    dispatchSuccessEvent(inputElement, file) {
        // สร้าง Event จำเพาะเพื่อให้โค้ดส่วนอื่น (เช่น ส่วนพรีวิวรูป หรือเตรียมยิง API) มาดักฟังได้
        const event = new CustomEvent('fileValidated', {
            bubbles: true,
            detail: { 
                file: file,
                inputName: inputElement.name 
            }
        });
        inputElement.dispatchEvent(event);
    }
}

// ---------------------------------------------------------
// การเรียกใช้งาน (Initialization)
// ---------------------------------------------------------
/*document.addEventListener('DOMContentLoaded', () => {
    // ประกาศใช้ Class ครั้งเดียว คลุมได้ทั้งหน้าเว็บ
    new FileUploaderValidator({
        selector: '.file-validator',
        defaultMaxSize: 2 
    });

    // ตัวอย่างการดักฟัง Event เมื่อไฟล์ผ่านการตรวจสอบ (Decoupled Logic)
    document.addEventListener('fileValidated', (e) => {
        const validFile = e.detail.file;
        console.log(`ไฟล์ ${validFile.name} พร้อมสำหรับส่งข้อมูลแล้ว!`);
        // สามารถเขียนโค้ด Preview รูปภาพ หรือเตรียมนำเข้า FormData ตรงนี้ได้เลย
    });
});*/
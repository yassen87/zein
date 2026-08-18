<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/services.php';
require_once __DIR__ . '/navigation.php';

function handle_download(string $route): void
{
    if ($route === 'reports' && !empty($_GET['export'])) {
        require_login();
        $key = preg_replace('/[^a-z_]/', '', (string) $_GET['export']);
        if (!has_permission('reports_' . $key)) {
            http_response_code(403);
            exit('غير مصرح');
        }
        if (current_user_location_id() !== null) {
            $_GET['location_id'] = (string) current_user_location_id();
        }
        output_csv('report-' . $key . '.csv', report_rows($key, $_GET));
    }

    if ($route !== 'backup' || empty($_GET['download'])) {
        return;
    }
    require_login();
    if (!has_permission('backup')) {
        http_response_code(403);
        exit('غير مصرح');
    }
    $file = backup_file_path((string) $_GET['download']);
    if (!$file) {
        http_response_code(404);
        exit('الملف غير موجود');
    }
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

function handle_post(string $route, array $user): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if ($route === 'products' && has_permission('products_view')) {
        if (post_string('action') === 'delete') {
            if (!has_permission('products_delete')) {
                throw new RuntimeException('غير مصرح لك بحذف المنتجات.');
            }
            $result = delete_product((int) post_string('id'));
            if ($result === 'permanently_deleted') {
                flash('تم حذف المنتج نهائياً بنجاح.');
            } else {
                flash('تم إخفاء المنتج وتعطيله لوجود فواتير أو حركات مخزن مرتبطة به.');
            }
            redirect('products');
        }
        throw new RuntimeException('طلب غير صالح.');
    }

    if ($route === 'product_create' && has_permission('products_add')) {
        add_products_batch($_POST);
        flash('تم إضافة المنتج/المنتجات بنجاح.');
        redirect('products');
    }

    if ($route === 'product_edit' && has_permission('products_edit')) {
        update_product($_POST);
        flash('تم تعديل المنتج.');
        redirect('products');
    }

    if ($route === 'recipes' && has_permission('recipes_view')) {
        if (post_string('action') === 'delete') {
            if (!has_permission('recipes_edit')) {
                throw new RuntimeException('غير مصرح لك بحذف التركيبات.');
            }
            delete_recipe((int) post_string('id'));
            flash('تم حذف التركيبة بنجاح.');
        } else {
            if (!has_permission('recipes_add')) {
                throw new RuntimeException('غير مصرح لك بإضافة أو تعديل التركيبات.');
            }
            $recipe_id = (int) post_string('id');
            if ($recipe_id > 0) {
                update_recipe($recipe_id, $_POST);
                flash('تم تعديل التركيبة بنجاح.');
            } else {
                add_recipe($_POST);
                flash('تم حفظ التركيبة الجاهزة.');
            }
        }
        redirect('recipes');
    }

    if ($route === 'formula_defaults' && has_permission('recipes_view')) {
        if (post_string('action') === 'delete') {
            if (!has_permission('recipes_edit')) {
                throw new RuntimeException('غير مصرح لك بحذف الجرامات الافتراضية.');
            }
            delete_formula_default((int) post_string('id'));
            flash('تم حذف إعداد الجرامات الافتراضية.');
        } else {
            if (!has_permission('recipes_add')) {
                throw new RuntimeException('غير مصرح لك بإضافة أو تعديل الجرامات الافتراضية.');
            }
            upsert_formula_default($_POST);
            flash('تم حفظ إعداد الجرامات الافتراضية بنجاح.');
        }
        redirect('formula_defaults');
    }

    if ($route === 'inventory' && has_permission('inventory_view')) {
        if (!has_permission('inventory_adjust')) {
            throw new RuntimeException('غير مصرح لك بتسوية المخزون.');
        }
        $action = post_string('action');
        $locationId = (int) post_string('location_id');
        require_location_access($locationId);
        require_location_type($locationId, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن تعديل رصيده.');

        if ($action === 'delete') {
            $movementIdStr = post_string('movement_id');
            $movementIds = explode(',', $movementIdStr);
            foreach ($movementIds as $id) {
                if (is_numeric($id)) {
                    delete_inventory_addition((int) $id, (int) $user['id']);
                }
            }
            flash('تم حذف إضافة المخزون بنجاح.');
        } elseif ($action === 'update') {
            update_inventory_addition($_POST, (int) $user['id']);
            flash('تم تعديل إضافة المخزون بنجاح.');
        } else {
            create_inventory_addition($_POST, (int) $user['id']);
            flash('تم تسجيل إضافة مخزون جديدة بنجاح.');
        }
        redirect('inventory');
    }

    if ($route === 'inventory_add' && has_permission('inventory_view')) {
        if (!has_permission('inventory_adjust')) {
            throw new RuntimeException('غير مصرح لك بتسوية المخزون.');
        }
        $locationId = (int) post_string('location_id');
        require_location_access($locationId);
        require_location_type($locationId, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن تعديل رصيده.');
        create_inventory_additions($_POST, (int) $user['id']);
        flash('تم تسجيل إضافات المخزون بنجاح.');
        redirect('inventory');
    }

    if ($route === 'transfers_supply' && has_permission('transfers')) {
        if (post_string('action') === 'receive') {
            receive_transfer((int) post_string('transfer_id'), (int) $user['id'], ['warehouse'], ['branch']);
            flash('تم استلام التوريد وإضافة الكمية للفرع المستلم.');
        } elseif (post_string('action') === 'cancel') {
            cancel_transfer((int) post_string('transfer_id'), (int) $user['id'], ['warehouse'], ['branch']);
            flash('تم إلغاء أمر التوريد وإعادة الكمية للمخزن.');
        } elseif (post_string('action') === 'update') {
            update_supply_transfer($_POST, (int) $user['id']);
            flash('تم تعديل أمر التوريد بنجاح.');
        } else {
            create_supply_transfer($_POST, (int) $user['id']);
            flash('تم إنشاء أمر التوريد وخصم الكمية من المخزن.');
        }
        redirect('transfers_supply');
    }

    if ($route === 'transfers_branch' && has_permission('transfers')) {
        if (post_string('action') === 'receive') {
            receive_transfer((int) post_string('transfer_id'), (int) $user['id'], ['branch'], ['branch']);
            flash('تم استلام التحويل وإضافة الكمية للفرع المستلم.');
        } elseif (post_string('action') === 'cancel') {
            cancel_transfer((int) post_string('transfer_id'), (int) $user['id'], ['branch'], ['branch']);
            flash('تم إلغاء التحويل وإعادة الكمية للفرع المرسل.');
        } elseif (post_string('action') === 'update') {
            update_branch_transfer($_POST, (int) $user['id']);
            flash('تم تعديل أمر التحويل بنجاح.');
        } else {
            create_branch_transfer($_POST, (int) $user['id']);
            flash('تم إنشاء أمر التحويل وخصم الكمية من الفرع المرسل.');
        }
        redirect('transfers_branch');
    }

    if ($route === 'returns' && has_permission('invoices')) {
        $returnType = post_string('return_type');
        if ($returnType === 'invoice') {
            $invoiceId = (int) post_string('invoice_id');
            $method    = post_string('refund_method', 'cash');
            $reason    = post_string('reason');
            if (!$invoiceId || !$reason) {
                throw new RuntimeException('الفاتورة والسبب مطلوبان.');
            }
            create_return_invoice($invoiceId, $method, $reason, (int) $user['id']);
            flash('تم تسجيل مرتجع الفاتورة بنجاح.');
        } elseif ($returnType === 'line') {
            $lineId = (int) post_string('line_id');
            $method = post_string('refund_method', 'cash');
            $reason = post_string('reason');
            if (!$lineId || !$reason) {
                throw new RuntimeException('بند الفاتورة والسبب مطلوبان.');
            }
            create_return_line_invoice($lineId, $method, $reason, (int) $user['id']);
            flash('تم تسجيل مرتجع البند بنجاح.');
        } else {
            throw new RuntimeException('نوع المرتجع غير معروف.');
        }
        redirect('returns');
    }

    if ($route === 'waste' && has_permission('inventory_view')) {
        if (!has_permission('inventory_adjust')) {
            throw new RuntimeException('غير مصرح لك بتسجيل هالك.');
        }
        $locationId = (int) post_string('location_id');
        require_location_access($locationId);
        require_location_type($locationId, ['warehouse', 'branch'], 'الأونلاين ليس مخزناً ولا يمكن تسجيل هالك عليه.');
        add_wasted_product([
            'location_id' => $locationId,
            'product_id' => (int) post_string('product_id'),
            'quantity' => post_float('quantity'),
            'reason' => post_string('reason'),
        ], (int) $user['id']);
        flash('تم تسجيل الهالك وخصمه من مخزون الموقع بنجاح.');
        redirect('waste');
    }

    if ($route === 'customers' && has_permission('customers_view')) {
        if (post_string('action') === 'pay_debt') {
            if (!has_permission('customers_pay_debt')) {
                throw new RuntimeException('غير مصرح لك بتسجيل سداد الديون.');
            }
            add_customer_payment((int) post_string('debt_id'), post_float('amount'), post_string('method', 'cash'), (int) $user['id']);
            flash('تم تسجيل دفعة الدين.');
        } elseif (post_string('action') === 'update') {
            if (!has_permission('customers_edit')) {
                throw new RuntimeException('غير مصرح لك بتعديل بيانات العملاء.');
            }
            update_customer($_POST);
            flash('تم تعديل بيانات العميل.');
        } elseif (post_string('action') === 'delete') {
            if (!has_permission('customers_edit')) {
                throw new RuntimeException('غير مصرح لك بحذف العملاء.');
            }
            $result = delete_customer((int) post_string('id'));
            if ($result === 'permanently_deleted') {
                flash('تم حذف العميل نهائياً بنجاح.');
            } else {
                flash('تم إخفاء العميل وتعطيله لوجود فواتير أو حركات مرتبطة به.');
            }
        } else {
            if (!has_permission('customers_add')) {
                throw new RuntimeException('غير مصرح لك بإضافة عملاء.');
            }
            add_customer([
                'name' => post_string('name'),
                'phone' => post_string('phone'),
                'source' => post_string('source', 'offline'),
                'notes' => post_string('notes'),
            ]);
            flash('تم إضافة العميل.');
        }
        redirect('customers');
    }

    if ($route === 'pos' && has_permission('pos')) {
        $invoiceId = create_invoice($_POST, $user);
        flash('تم إنشاء الفاتورة رقم #' . $invoiceId . ' وخصم المخزون.');
        // Redirect back to POS and request the client to open the printable invoice in a new window
        // also instruct client to clear the POS cart
        redirect('pos&print_invoice=' . $invoiceId . '&clear_cart=1');
    }

    if ($route === 'invoices' && has_permission('invoices_notes')) {
        if (post_string('action') === 'update_notes') {
            $invoiceId = (int) post_string('invoice_id');
            $notes = post_string('notes');
            $db = pdo();
            $stmt = $db->prepare('UPDATE invoices SET notes = ? WHERE id = ?');
            $stmt->execute([$notes, $invoiceId]);
            flash('تم تحديث ملاحظات الفاتورة.');
            redirect('invoices');
        }
    }

    if ($route === 'returns' && has_permission('returns')) {
        if (post_string('return_type') === 'line') {
            create_return_line_invoice((int) post_string('line_id'), post_string('refund_method', 'cash'), post_string('reason'), (int) $user['id']);
            flash('تم تنفيذ مرتجع البند وإرجاع المخزون.');
        } else {
            create_return_invoice((int) post_string('invoice_id'), post_string('refund_method', 'cash'), post_string('reason'), (int) $user['id']);
            flash('تم تنفيذ المرتجع وإرجاع المخزون.');
        }
        redirect('returns');
    }

    if ($route === 'shifts' && has_permission('shifts')) {
        $action = post_string('action', 'close');
        if ($action === 'update') {
            update_shift_closure((int) post_string('id'), post_float('actual_cash'), post_string('notes'));
            flash('تم تعديل الشيفت.');
        } elseif ($action === 'delete') {
            delete_shift_closure((int) post_string('id'));
            flash('تم حذف الشيفت.');
        } else {
            close_shift((int) post_string('location_id'), post_float('actual_cash'), post_string('notes'), $user);
            flash('تم إغلاق الشيفت وتسجيل الفرق.');
        }
        redirect('shifts');
    }

    if ($route === 'attendance') {
        if (post_string('action') === 'generate_qr') {
            if (!has_permission('attendance')) {
                throw new RuntimeException('غير مصرح لك بتوليد رمز QR.');
            }
            $locationId = (int) post_string('location_id');
            require_location_type($locationId, ['warehouse', 'branch'], 'الأونلاين لا يتم توليد QR حضور له.');
            $token = 'LOC-' . $locationId . '-' . bin2hex(random_bytes(8));
            $db = pdo();
            $stmt = $db->prepare('UPDATE locations SET qr_code = ? WHERE id = ?');
            $stmt->execute([$token, $locationId]);
            flash('تم توليد رمز QR بنجاح.');
            redirect('attendance&tab=qrcodes');
        } elseif (post_string('action') === 'update_location_geo') {
            if (!has_permission('users_permissions')) {
                throw new RuntimeException('غير مصرح لك بتعديل موقع الفرع.');
            }
            $locationId = (int) post_string('location_id');
            require_location_type($locationId, ['warehouse', 'branch'], 'الأونلاين لا يتم تسجيل حضور أو انصراف له.');
            $lat = post_float('latitude');
            $lng = post_float('longitude');
            $db = pdo();
            $stmt = $db->prepare('UPDATE locations SET latitude = ?, longitude = ? WHERE id = ?');
            $stmt->execute([$lat, $lng, $locationId]);
            flash('تم تحديث الإحداثيات الجغرافية للموقع بنجاح.');
            redirect('attendance&tab=qrcodes');
        } elseif (post_string('action') === 'qr_scan') {
            $token = post_string('qr_token');
            $scanAction = post_string('scan_action');
            $lat = post_string('latitude') !== '' ? post_float('latitude') : null;
            $lng = post_string('longitude') !== '' ? post_float('longitude') : null;
            
            $db = pdo();
            $stmt = $db->prepare('SELECT * FROM locations WHERE qr_code = ? AND is_active = 1');
            $stmt->execute([$token]);
            $loc = $stmt->fetch();
            if (!$loc) {
                flash('رمز QR غير صالح أو الموقع غير نشط.', 'danger');
                redirect('attendance');
            }
            if ($loc['type'] === 'online') {
                throw new RuntimeException('الأونلاين لا يتم تسجيل حضور أو انصراف له.');
            }
            require_location_access((int) $loc['id']);

            // GPS Lock verification (must be within 10 meters)
            if ($loc['latitude'] === null || $loc['longitude'] === null) {
                throw new RuntimeException('إحداثيات هذا الفرع غير مسجلة بالنظام. يرجى مراجعة الإدارة.');
            }
            if ($lat === null || $lng === null) {
                throw new RuntimeException('يرجى تفعيل الـ GPS والسماح للمتصفح بالوصول لموقعك الجغرافي لتسجيل الحضور.');
            }
            $distance = calculate_distance((float)$lat, (float)$lng, (float)$loc['latitude'], (float)$loc['longitude']);
            if ($distance > 10.0) {
                throw new RuntimeException('أنت بعيد جداً عن الفرع. المسافة الحالية: ' . round($distance, 1) . ' متر. يجب أن تكون على بعد 10 أمتار على الأكثر لتسجيل حضور/انصراف.');
            }

            // Double scan / status check verification
            $stmtStatus = $db->prepare("SELECT action FROM attendance_records WHERE user_id = ? AND DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 1");
            $stmtStatus->execute([$user['id']]);
            $currentStatus = $stmtStatus->fetchColumn();
            
            if ($scanAction === 'check_in' && $currentStatus === 'check_in') {
                throw new RuntimeException('لقد قمت بتسجيل الحضور بالفعل اليوم.');
            }
            if ($scanAction === 'check_out' && ($currentStatus === false || $currentStatus === 'check_out')) {
                throw new RuntimeException('يجب تسجيل الحضور أولاً قبل تسجيل الانصراف.');
            }
            
            $stmt = $db->prepare('INSERT INTO attendance_records (user_id, location_id, action, source, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user['id'], $loc['id'], $scanAction, 'qr', $lat, $lng]);
            
            flash(($scanAction === 'check_in' ? 'تم تسجيل حضورك بنجاح في ' : 'تم تسجيل انصرافك بنجاح من ') . $loc['name']);
            redirect('attendance');
        } else {
            if (!has_permission('attendance')) {
                throw new RuntimeException('غير مصرح لك بتسجيل الحضور.');
            }
            add_attendance($_POST, (int) $user['id']);
            flash('تم تسجيل الحضور/الانصراف.');
            redirect('attendance');
        }
    }

    if ($route === 'targets' && has_permission('targets')) {
        $action = post_string('action', 'save');
        if ($action === 'delete') {
            delete_target((int) post_string('id'));
            flash('تم حذف التارجت اليومي.');
        } elseif ($action === 'update') {
            $locationId = (int) post_string('location_id');
            update_target((int) post_string('id'), $locationId, post_string('target_date'), post_float('target_amount'), (int) $user['id']);
            flash('تم تعديل التارجت اليومي.');
        } else {
            $locationId = (int) post_string('location_id');
            require_location_access($locationId);
            upsert_target($locationId, post_string('target_date'), post_float('target_amount'), (int) $user['id']);
            flash('تم حفظ التارجت اليومي.');
        }
        redirect('targets');
    }

    if ($route === 'expenses' && has_permission('expenses_view')) {
        if (!has_permission('expenses_add')) {
            throw new RuntimeException('غير مصرح لك بتسجيل مصاريف.');
        }
        add_expense($_POST, (int) $user['id']);
        flash('تم تسجيل المصروف.');
        redirect('expenses');
    }

    if ($route === 'suppliers' && has_permission('suppliers_view')) {
        if (!has_permission('suppliers_add')) {
            throw new RuntimeException('غير مصرح لك بتسجيل موردين.');
        }
        add_supplier($_POST, (int) $user['id']);
        flash('تم تسجيل المورد.');
        redirect('suppliers');
    }

    if ($route === 'users' && has_permission('users_view')) {
        if (post_string('action') === 'save_permissions') {
            if (!has_permission('users_permissions')) {
                throw new RuntimeException('غير مصرح لك بتعديل الصلاحيات.');
            }
            $db = pdo();
            $db->beginTransaction();
            try {
                $db->exec('DELETE FROM role_permissions');
                $posted_perms = $_POST['perms'] ?? [];
                $stmt = $db->prepare('INSERT INTO role_permissions (role_id, permission_code) VALUES (?, ?)');
                foreach ($posted_perms as $role_id => $perms) {
                    foreach (array_keys($perms) as $perm_code) {
                        $stmt->execute([(int) $role_id, $perm_code]);
                    }
                }
                $db->commit();
                unset($_SESSION['permissions']);
                flash('تم حفظ الصلاحيات بنجاح.');
                redirect('users&tab=permissions');
            } catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
        } elseif (post_string('action') === 'add_role') {
            if (!has_permission('users_permissions')) {
                throw new RuntimeException('غير مصرح لك بإضافة أدوار جديدة.');
            }
            $name = post_string('name');
            $code = preg_replace('/[^a-z0-9_]/', '', strtolower(post_string('code')));
            if ($name && $code) {
                $db = pdo();
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare('INSERT IGNORE INTO roles (code, name) VALUES (?, ?)');
                    $stmt->execute([$code, $name]);
                    $role_id = (int) $db->lastInsertId();
                    
                    if ($role_id > 0) {
                        $selected_perms = $_POST['new_role_perms'] ?? [];
                        $stmt_perm = $db->prepare('INSERT INTO role_permissions (role_id, permission_code) VALUES (?, ?)');
                        foreach (array_keys($selected_perms) as $perm_code) {
                            $stmt_perm->execute([$role_id, $perm_code]);
                        }
                        $db->commit();
                        unset($_SESSION['permissions']);
                        flash('تم إضافة الدور الجديد بنجاح مع الصلاحيات المحددة.');
                    } else {
                        $db->rollBack();
                        flash('كود الدور مسجل مسبقاً، يرجى اختيار كود آخر.', 'danger');
                    }
                } catch (Throwable $e) {
                    $db->rollBack();
                    throw $e;
                }
            } else {
                flash('اسم الدور أو الكود غير صالح.', 'danger');
            }
            redirect('users&tab=permissions');
        } elseif (post_string('action') === 'delete_role') {
            if (!has_permission('users_permissions')) {
                throw new RuntimeException('غير مصرح لك بحذف الأدوار.');
            }
            $role_id = (int) post_string('role_id');
            $db = pdo();
            
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
            $stmt->execute([$role_id]);
            $count = (int) $stmt->fetchColumn();
            
            if ($count > 0) {
                flash('لا يمكن حذف هذا الدور لوجود موظفين مسجلين به حالياً.', 'danger');
            } else {
                $db->beginTransaction();
                try {
                    $db->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$role_id]);
                    $db->prepare('DELETE FROM roles WHERE id = ?')->execute([$role_id]);
                    $db->commit();
                    unset($_SESSION['permissions']);
                    flash('تم حذف الدور بنجاح.');
                } catch (Throwable $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
            redirect('users&tab=permissions');
        } elseif (post_string('action') === 'deactivate') {
            if (!has_permission('users_add')) {
                throw new RuntimeException('غير مصرح لك بتعطيل الموظفين.');
            }
            $id = (int) post_string('id');
            if ($id === (int) $user['id']) {
                throw new RuntimeException('لا يمكن تعطيل حسابك الحالي.');
            }
            deactivate_user($id);
            flash('تم تعطيل الموظف.');
        } elseif (post_string('action') === 'delete') {
            if (!has_permission('users_add')) {
                throw new RuntimeException('غير مصرح لك بحذف الموظفين.');
            }
            $id = (int) post_string('id');
            if ($id === (int) $user['id']) {
                throw new RuntimeException('لا يمكن حذف حسابك الحالي نهائياً.');
            }
            delete_user_permanently($id);
            flash('تم حذف الموظف نهائياً.');
        } elseif (post_string('action') === 'update') {
            if (!has_permission('users_add')) {
                throw new RuntimeException('غير مصرح لك بتعديل الموظفين.');
            }
            update_user($_POST);
            flash('تم تعديل بيانات الموظف.');
        } else {
            if (!has_permission('users_add')) {
                throw new RuntimeException('غير مصرح لك بإضافة موظفين.');
            }
            add_user($_POST);
            flash('تم إنشاء حساب الموظف.');
        }
    }

    if ($route === 'payroll' && has_permission('users_view')) {
        if (post_string('action') === 'update_rates') {
            if (!has_permission('users_permissions')) {
                throw new RuntimeException('غير مصرح لك بتعديل الرواتب.');
            }
            $userId = (int) post_string('user_id');
            $salary = post_float('basic_salary');
            $comm = post_float('commission_percent');
            $db = pdo();
            $stmt = $db->prepare('UPDATE users SET basic_salary = ?, commission_percent = ? WHERE id = ?');
            $stmt->execute([$salary, $comm, $userId]);
            flash('تم تحديث الراتب والعمولة للموظف بنجاح.');
            redirect('payroll');
        }
    }

    if ($route === 'online_orders' && has_permission('online_orders')) {
        if (post_string('action') === 'status') {
            update_online_order_status((int) post_string('order_id'), post_string('status'), (int) $user['id']);
            flash('تم تحديث حالة الطلب.');
        } elseif (post_string('action') === 'delete') {
            delete_online_order((int) post_string('order_id'), (int) $user['id']);
            flash('تم حذف الطلب الأونلاين.');
        } elseif (post_string('action') === 'update') {
            update_online_order($_POST, (int) $user['id']);
            flash('تم تحديث طلب الأونلاين.');
        } else {
            create_online_order($_POST, (int) $user['id']);
            flash('تم إنشاء طلب أونلاين.');
        }
        redirect('online_orders');
    }

    if ($route === 'locations' && has_permission('settings')) {
        $action = post_string('action');
        if ($action === 'update') {
            update_location_data($_POST, (int) $user['id']);
            flash('تم تعديل بيانات الفرع/الموقع.');
        } elseif ($action === 'deactivate') {
            set_location_active((int) post_string('id'), false, (int) $user['id']);
            flash('تم تعطيل الفرع/الموقع.');
        } elseif ($action === 'activate') {
            set_location_active((int) post_string('id'), true, (int) $user['id']);
            flash('تم تفعيل الفرع/الموقع.');
        } else {
            add_location($_POST, (int) $user['id']);
            flash('تم إضافة الفرع/الموقع.');
        }
        redirect('locations');
    }

    if ($route === 'backup' && has_permission('backup')) {
        $action = post_string('action');
        if ($action === 'reset') {
            reset_database();
            flash('تم تفريغ البيانات وإعادة تهيئة النظام من البداية. بيانات الدخول الافتراضية: admin / admin123');
        } else {
            $file = backup_database((int) $user['id']);
            flash('تم إنشاء نسخة احتياطية: ' . basename($file));
        }
        redirect('backup');
    }

    if ($route === 'settings' && has_permission('settings')) {
        update_settings($_POST, (int) $user['id']);
        flash('تم حفظ إعدادات النظام.');
        redirect('settings');
    }
}

function render_page(string $route, array $user): void
{
    $allowed = all_routes();

    if (!in_array($route, $allowed, true)) {
        $route = 'dashboard';
    }

    if (!has_permission($route)) {
        echo '<div class="alert danger">غير مصرح لك بدخول هذه الصفحة.</div>';
        return;
    }

    $file = page_path_for_route($route);
    if (!is_file($file)) {
        $file = __DIR__ . '/pages/main/dashboard.php';
    }

    require $file;
}
